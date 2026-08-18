<?php

namespace App\Services\Curriculum;

use App\Models\CurriculumFragment;
use App\Models\CurriculumSource;
use App\Models\RepositoryImport;
use App\Models\RepositoryImportItem;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class AcademicRepositoryIngestionService
{
    /** Sized for the verified Lesson_Notes archive while retaining ZIP-bomb safeguards. */
    public const MAX_FILES = 8000;

    public const MAX_EXPANDED = 3221225472;

    public function ingest(UploadedFile $file, array $meta, int $actor): RepositoryImport
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $import = RepositoryImport::create([
            'filename' => $file->getClientOriginalName(),
            'format' => $extension,
            'uploaded_by' => $actor,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            if ($extension === 'zip') {
                $this->zip($file, $meta, $actor, $import);
            } elseif (in_array($extension, ['xlsx', 'xls'], true)) {
                $this->xlsx($file, $meta, $actor, $import);
            } else {
                $this->document($file->getRealPath(), $file->getClientOriginalName(), $file->getMimeType(), $meta, $actor, $import);
            }

            $import->update([
                'status' => $import->failed ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $import->update([
                'status' => 'failed',
                'failed' => DB::raw('failed + 1'),
                'metadata' => ['error' => Str::limit($exception->getMessage(), 500)],
            ]);

            throw $exception;
        }

        return $import->fresh();
    }

    /**
     * Read the mixed archive conventions and return one platform hierarchy:
     * Class -> Subject -> Term. The exact source path is always retained.
     */
    public function classifyPath(string $path): array
    {
        $originalPath = trim(preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?? $path, '/');
        $segments = array_values(array_filter(explode('/', $originalPath), fn ($part) => trim($part) !== ''));
        $filename = array_pop($segments) ?: basename($originalPath);

        while ($segments && $this->isWrapperSegment($segments[0])) {
            array_shift($segments);
        }

        $classIndex = null;
        $classLabel = null;
        foreach ($segments as $index => $segment) {
            $detected = $this->extractClassLabel($segment);
            if ($detected !== null) {
                $classIndex = $index;
                $classLabel = $detected;
                break;
            }
        }

        $classLabel ??= $this->extractClassLabel($filename);
        if ($classLabel === null && collect($segments)->contains(fn ($segment) => preg_match('/general\s+lesson\s+plans?/i', $segment))) {
            $classLabel = 'General';
        }
        $classLabel ??= 'General';

        $termLabel = null;
        foreach ($segments as $segment) {
            $termLabel = $this->extractTermLabel($segment);
            if ($termLabel !== null) {
                break;
            }
        }
        $termLabel ??= $this->extractTermLabel($filename);
        if ($termLabel === null && $classLabel === 'General') {
            $termLabel = 'General';
        }
        $termLabel ??= 'General';

        $subjectLabel = null;
        if ($classIndex !== null) {
            $before = $segments[$classIndex - 1] ?? null;
            $after = $segments[$classIndex + 1] ?? null;

            if ($before !== null && !$this->isStructuralSegment($before)) {
                $subjectLabel = $this->normaliseSubjectLabel($before);
            } elseif ($after !== null && !$this->isStructuralSegment($after)) {
                $subjectLabel = $this->normaliseSubjectLabel($after);
            }
        }

        if ($subjectLabel === null) {
            foreach ($segments as $segment) {
                if (!$this->isStructuralSegment($segment)) {
                    $subjectLabel = $this->normaliseSubjectLabel($segment);
                    break;
                }
            }
        }
        $subjectLabel ??= 'General';

        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $title = preg_replace('/^\d+\s*[-–—]\s*/u', '', $basename) ?? $basename;
        $title = preg_replace('/^(?:(?:term\s*[123])|(?:(?:1st|2nd|3rd|first|second|third)\s+term))\s*[-:–—]?\s*/iu', '', $title) ?? $title;
        $title = preg_replace('/\b(?:20\d{2}\s+)?week\s*\d+(?:\s*[&-]\s*\d+)?\s*[-:–—]?\s*/iu', '', $title) ?? $title;
        $title = trim(preg_replace('/\s+/u', ' ', str_replace('_', ' ', $title)) ?? $title, " \t\n\r\0\x0B-–—:;");

        preg_match('/\bweek\s*(\d+)/i', $originalPath, $weekMatch);
        $weekNumber = isset($weekMatch[1]) ? (int) $weekMatch[1] : null;

        $classFolder = Str::slug($classLabel ?: 'Unmapped Class');
        $subjectFolder = Str::slug($subjectLabel ?: 'Unmapped Subject');
        $termFolder = Str::slug($termLabel ?: 'Unmapped Term');

        return [
            'original_path' => $originalPath,
            'class_label' => $classLabel,
            'subject_label' => $subjectLabel,
            'term_label' => $termLabel,
            'title' => $title !== '' ? $title : $basename,
            'week_number' => $weekNumber,
            'storage_hierarchy' => "{$classFolder}/{$subjectFolder}/{$termFolder}",
        ];
    }

    private function document(string $path, string $name, string $mime, array $meta, int $actor, RepositoryImport $import, ?string $relative = null): void
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $relativePath = $relative ?: $name;

        if (!in_array($extension, ['docx', 'doc', 'pdf'], true)) {
            $this->item($import, $relativePath, 'unsupported', 'Unsupported archive entry.');
            return;
        }

        $meta = $this->infer($relativePath, $meta);
        $checksum = hash_file('sha256', $path);
        $duplicate = CurriculumSource::whereNull('tenant_id')->where('checksum', $checksum)->first();

        if ($duplicate) {
            $import->increment('duplicates');
            $this->item($import, $relativePath, 'duplicate', 'Matching checksum already exists.', $duplicate->id, $meta['metadata'] ?? []);
            return;
        }

        [$raw, $locator] = match ($extension) {
            'docx' => $this->docx($path),
            'doc' => $this->doc($path),
            default => $this->pdf($path),
        };

        $clean = $this->clean($raw);
        $safeTitle = Str::limit(Str::slug(pathinfo($name, PATHINFO_FILENAME)), 90, '');
        $storedName = substr($checksum, 0, 12).'-'.($safeTitle ?: 'resource').'.'.$extension;
        $storageDirectory = 'academic-repository/originals/'.($meta['metadata']['storage_hierarchy'] ?? 'unmapped-class/unmapped-subject/unmapped-term');
        $stored = Storage::disk('local')->putFileAs($storageDirectory, new File($path), $storedName);
        $status = mb_strlen($clean) >= 80 ? 'extracted' : 'failed';

        $source = DB::transaction(function () use ($meta, $actor, $name, $mime, $checksum, $stored, $raw, $clean, $status, $extension, $locator) {
            $metadata = array_filter(array_merge($meta['metadata'] ?? [], [
                'format' => $extension,
                'source_locator' => $locator,
            ]), fn ($value) => $value !== null && $value !== '');

            $source = CurriculumSource::create([
                'tenant_id' => null,
                'subject_id' => $meta['subject_id'] ?? null,
                'class_level_id' => $meta['curriculum_level_id'] ?? null,
                'source_class_level_id' => $meta['source_class_level_id'] ?? null,
                'curriculum_level_id' => $meta['curriculum_level_id'] ?? null,
                'term_id' => $meta['term_id'] ?? null,
                'week_number' => $meta['week_number'] ?? null,
                'authority' => $meta['authority'] ?? 'OTHER',
                'source_type' => $meta['source_type'] ?? 'curriculum_document',
                'title' => $meta['title'] ?? pathinfo($name, PATHINFO_FILENAME),
                'original_filename' => basename(str_replace('\\', '/', $name)),
                'version' => $meta['version'] ?? date('Y'),
                'source_file_path' => $stored,
                'mime_type' => $mime,
                'file_size' => Storage::disk('local')->size($stored),
                'checksum' => $checksum,
                'raw_text' => $raw,
                'cleaned_text' => $clean,
                'extraction_status' => $status,
                'index_status' => $status === 'extracted' ? 'indexed' : 'failed',
                'is_official' => (bool) ($meta['is_official'] ?? false),
                'is_active' => false,
                'needs_review' => true,
                'review_status' => 'pending',
                'created_by' => $actor,
                'rights_status' => $meta['rights_status'] ?? 'institution_authorised',
                'metadata' => $metadata,
            ]);

            if ($status === 'extracted') {
                foreach ($this->chunks($clean) as $index => $chunk) {
                    CurriculumFragment::create([
                        'curriculum_source_id' => $source->id,
                        'subject_id' => $source->subject_id,
                        'class_level_id' => $source->curriculum_level_id,
                        'topic' => $meta['topic'] ?? $source->title,
                        'subtopic' => $meta['subtopic'] ?? null,
                        'content' => $chunk,
                        'source_locator' => $extension === 'pdf' ? 'Extracted page text' : 'Document section '.($index + 1),
                        'sequence' => $index,
                    ]);
                }
            }

            return $source;
        });

        $import->increment('discovered');
        $status === 'extracted' ? $import->increment('imported') : $import->increment('failed');
        $import->increment('needs_review');
        $this->item(
            $import,
            $relativePath,
            $status === 'extracted' ? 'needs_review' : 'failed',
            $status === 'extracted' ? 'Extracted and indexed; verify metadata.' : 'No readable text; scanned PDF may require OCR.',
            $source->id,
            $meta['metadata'] ?? []
        );
    }

    private function zip(UploadedFile $file, array $meta, int $actor, RepositoryImport $import): void
    {
        $archive = new ZipArchive();
        if ($archive->open($file->getRealPath()) !== true) {
            throw new \RuntimeException('Invalid ZIP archive.');
        }

        try {
            if ($archive->numFiles > self::MAX_FILES) {
                throw new \RuntimeException('Archive file-count limit exceeded (maximum 8,000 files).');
            }

            $expanded = 0;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $stat = $archive->statIndex($index);
                if ($stat === false) {
                    continue;
                }

                $name = str_replace('\\', '/', $stat['name']);
                $expanded += (int) $stat['size'];

                if ($expanded > self::MAX_EXPANDED) {
                    throw new \RuntimeException('Archive expanded-size limit exceeded (maximum 3 GB).');
                }
                if (str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name) || preg_match('/^[A-Za-z]:/', $name)) {
                    throw new \RuntimeException('Unsafe ZIP path detected.');
                }
                if (str_ends_with($name, '/')) {
                    continue;
                }

                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($extension, ['docx', 'doc', 'pdf', 'xlsx', 'xls'], true)) {
                    $this->item($import, $name, 'unsupported', 'Only DOCX, DOC, PDF, XLSX and XLS entries are supported.');
                    continue;
                }

                $temporary = tempnam(sys_get_temp_dir(), 'edurepo_');
                try {
                    file_put_contents($temporary, $archive->getFromIndex($index));
                    if (in_array($extension, ['xlsx', 'xls'], true)) {
                        $this->xlsxPath($temporary, $name, $this->infer($name, $meta), $actor, $import);
                    } else {
                        $this->document($temporary, basename($name), $this->mime($extension), $meta, $actor, $import, $name);
                    }
                } finally {
                    @unlink($temporary);
                }
            }
        } finally {
            $archive->close();
        }
    }

    private function xlsx(UploadedFile $file, array $meta, int $actor, RepositoryImport $import): void
    {
        $this->xlsxPath($file->getRealPath(), $file->getClientOriginalName(), $this->infer($file->getClientOriginalName(), $meta), $actor, $import);
    }

    private function xlsxPath(string $path, string $name, array $meta, int $actor, RepositoryImport $import): void
    {
        [$headers, $rows] = $this->sheet($path);
        $import->increment('discovered', count($rows));
        $mapping = $meta['column_mapping'] ?? [];
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $fileChecksum = hash_file('sha256', $path);
        $safeTitle = Str::limit(Str::slug(pathinfo($name, PATHINFO_FILENAME)), 90, '');
        $storedName = substr($fileChecksum, 0, 12).'-'.($safeTitle ?: 'spreadsheet').'.'.$extension;
        $storageDirectory = 'academic-repository/originals/'.($meta['metadata']['storage_hierarchy'] ?? 'general/general/general');
        $stored = Storage::disk('local')->putFileAs($storageDirectory, new File($path), $storedName);

        foreach ($rows as $rowNumber => $row) {
            $values = [];
            foreach ($mapping as $field => $header) {
                $index = array_search($header, $headers, true);
                if ($index !== false) {
                    $values[$field] = $row[$index] ?? null;
                }
            }

            if (empty($values['content']) || empty($values['title'])) {
                $import->increment('failed');
                $this->item($import, $name.' row '.($rowNumber + 2), 'failed', 'Title and Content mappings are required.');
                continue;
            }

            $checksum = hash('sha256', $values['content']);
            if (CurriculumSource::whereNull('tenant_id')->where('checksum', $checksum)->exists()) {
                $import->increment('duplicates');
                continue;
            }

            $cleaned = $this->clean($values['content']);
            $source = CurriculumSource::create([
                'tenant_id' => null,
                'subject_id' => $meta['subject_id'] ?? null,
                'curriculum_level_id' => $meta['curriculum_level_id'] ?? null,
                'term_id' => $meta['term_id'] ?? null,
                'week_number' => $meta['week_number'] ?? null,
                'authority' => $values['authority'] ?? ($meta['authority'] ?? 'OTHER'),
                'source_type' => $values['resource_type'] ?? ($meta['source_type'] ?? 'curriculum_document'),
                'title' => $values['title'],
                'original_filename' => basename(str_replace('\\', '/', $name)),
                'version' => $values['source_year'] ?? date('Y'),
                'source_file_path' => $stored,
                'mime_type' => $this->mime($extension),
                'file_size' => Storage::disk('local')->size($stored),
                'checksum' => $checksum,
                'raw_text' => $values['content'],
                'cleaned_text' => $cleaned,
                'extraction_status' => 'extracted',
                'index_status' => 'indexed',
                'is_active' => false,
                'needs_review' => true,
                'review_status' => 'pending',
                'created_by' => $actor,
                'rights_status' => $meta['rights_status'] ?? 'institution_authorised',
                'metadata' => array_filter(array_merge($meta['metadata'] ?? [], ['spreadsheet_row' => $rowNumber + 2])),
            ]);

            foreach ($this->chunks($cleaned) as $index => $chunk) {
                CurriculumFragment::create([
                    'curriculum_source_id' => $source->id,
                    'subject_id' => $source->subject_id,
                    'class_level_id' => $source->curriculum_level_id,
                    'topic' => $values['topic'] ?? $source->title,
                    'subtopic' => $values['subtopic'] ?? null,
                    'content' => $chunk,
                    'source_locator' => 'Spreadsheet row '.($rowNumber + 2),
                    'sequence' => $index,
                ]);
            }

            $import->increment('imported');
            $import->increment('needs_review');
        }
    }

    private function infer(string $path, array $meta): array
    {
        $classification = $this->classifyPath($path);
        $meta['title'] ??= $classification['title'];
        $meta['week_number'] ??= $classification['week_number'];
        $meta['source_type'] ??= 'lesson_note';
        $meta['metadata'] = array_filter(array_merge($meta['metadata'] ?? [], [
            'original_path' => $classification['original_path'],
            'class_label' => $classification['class_label'],
            'subject_label' => $classification['subject_label'],
            'term_label' => $classification['term_label'],
            'storage_hierarchy' => $classification['storage_hierarchy'],
        ]), fn ($value) => $value !== null && $value !== '');

        return $meta;
    }

    private function extractClassLabel(string $value): ?string
    {
        $patterns = [
            '/\b(?:primary|pry|basic|basis)\s*[- ]?\s*(\d+)\b/i' => 'Primary ',
            '/\b(?:j[\s._-]*s[\s._-]*s|junior\s+secondary)\s*[- ]?\s*(\d+)\b/i' => 'JSS ',
            '/\b(?:s[\s._-]*s[\s._-]*s|s[\s._-]*s|senior\s+secondary)\s*[- ]?\s*(\d+)\b/i' => 'SS ',
            '/\b(?:nursery)\s*[- ]?\s*(\d+)\b/i' => 'Nursery ',
            '/\b(?:kg|kindergarten)\s*[- ]?\s*(\d+)\b/i' => 'KG ',
            '/\b(?:grade)\s*[- ]?\s*(\d+)\b/i' => 'Grade ',
        ];

        foreach ($patterns as $pattern => $prefix) {
            if (preg_match($pattern, $value, $match)) {
                return $prefix.$match[1];
            }
        }

        if (preg_match('/\bcreche\b/i', $value)) {
            return 'Creche';
        }
        if (preg_match('/\bpre[\s-]*nursery\b/i', $value)) {
            return 'Pre-Nursery';
        }
        if (preg_match('/\breception(?:\s*\(pre\s*school\))?\b/i', $value)) {
            return 'Reception';
        }

        return null;
    }

    private function extractTermLabel(string $value): ?string
    {
        $patterns = [
            'First Term' => '/\b(?:(?:1\s*st|ist|first)\s+term|term\s*(?:1|one))\b/i',
            'Second Term' => '/\b(?:(?:2\s*nd|second)\s+term|term\s*(?:2|two))\b/i',
            'Third Term' => '/\b(?:(?:3\s*(?:rd|th)|third)\s+term|term\s*(?:3|three))\b/i',
        ];

        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $value)) {
                return $label;
            }
        }

        return null;
    }

    private function isWrapperSegment(string $value): bool
    {
        $normalised = mb_strtolower(trim(str_replace(['_', '-'], ' ', $value)));

        return in_array($normalised, ['lesson notes', 'lesson note', 'repository', 'academic repository'], true)
            || str_contains($normalised, 'lesson notes repository');
    }

    private function isStructuralSegment(string $value): bool
    {
        return $this->isWrapperSegment($value)
            || $this->extractClassLabel($value) !== null
            || $this->extractTermLabel($value) !== null;
    }

    private function normaliseSubjectLabel(string $value): string
    {
        $label = trim(preg_replace('/\s+/u', ' ', str_replace('_', ' ', $value)) ?? $value, " \t\n\r\0\x0B-–—");

        return match (mb_strtolower($label)) {
            'phe' => 'PHE',
            default => $label,
        };
    }

    private function docx(string $path): array
    {
        $archive = new ZipArchive();
        if ($archive->open($path) !== true) {
            return ['', 'document'];
        }

        $xml = $archive->getFromName('word/document.xml') ?: '';
        $archive->close();

        return [
            html_entity_decode(strip_tags(str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8'),
            'document',
        ];
    }

    private function doc(string $path): array
    {
        $raw = file_get_contents($path) ?: '';
        if (str_starts_with($raw, '{\\rtf')) {
            $raw = preg_replace('/\\\\[a-z]+-?\d* ?|[{}]/i', ' ', $raw) ?? $raw;
        } else {
            $raw = preg_replace('/[^\x20-\x7E\r\n]/', ' ', $raw) ?? $raw;
        }

        return [$raw, 'legacy DOC'];
    }

    private function pdf(string $path): array
    {
        $raw = file_get_contents($path) ?: '';
        preg_match_all('/\(([^()]*(?:\\.[^()]*)*)\)\s*Tj/s', $raw, $matches);
        $text = implode("\n", array_map(fn ($value) => stripcslashes($value), $matches[1] ?? []));

        return [$text, 'pages'];
    }

    private function sheet(string $path): array
    {
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray('', true, true, false);

        return [array_map('trim', array_shift($rows) ?: []), $rows];
    }

    private function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private function chunks(string $text): array
    {
        $chunks = [];
        $length = mb_strlen($text);
        for ($offset = 0; $offset < $length; $offset += 2200) {
            $chunk = trim(mb_substr($text, $offset, 2200));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return $chunks;
    }

    private function mime(string $extension): string
    {
        return [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
        ][$extension] ?? 'application/octet-stream';
    }

    private function item(RepositoryImport $import, string $path, string $status, string $message, ?int $sourceId = null, array $metadata = []): void
    {
        RepositoryImportItem::create([
            'repository_import_id' => $import->id,
            'curriculum_source_id' => $sourceId,
            'relative_path' => $path,
            'status' => $status,
            'message' => $message,
            'inferred_metadata' => $metadata ?: null,
        ]);
    }
}
