<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\AscReturn;
use App\Models\AscSectionData;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AscSectionController extends Controller
{
    public function show(Request $request, string $section)
    {
        $section = strtoupper($section);
        $definition = config("asc.sections.{$section}");
        abort_unless($definition, 404);

        $tenant = auth()->user()->tenant;
        $year = $request->integer('year', now()->year);
        $record = AscSectionData::where('tenant_id', $tenant->id)
            ->where('census_year', $year)
            ->where('section', $section)
            ->first();

        $ascReturn = AscReturn::where('tenant_id', $tenant->id)
            ->where('census_year', $year)
            ->first();

        return view('asc.section', compact('tenant', 'year', 'section', 'definition', 'record', 'ascReturn'));
    }

    public function save(Request $request, string $section)
    {
        $section = strtoupper($section);
        $definition = config("asc.sections.{$section}");
        abort_unless($definition, 404);

        $tenant = auth()->user()->tenant;
        $validated = $request->validate([
            'census_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'fields' => ['nullable', 'array'],
        ]);

        $ascReturn = AscReturn::where('tenant_id', $tenant->id)
            ->where('census_year', $validated['census_year'])
            ->first();

        if ($ascReturn?->isLocked()) {
            return redirect()->route('asc.section.show', ['section' => strtolower($section), 'year' => $validated['census_year']])
                ->with('error', 'This census return has been finalized and is locked.');
        }

        $fields = $this->sanitize($validated['fields'] ?? [], $definition['fields'] ?? []);
        $isComplete = $this->isComplete($fields, $definition['fields'] ?? []);
        $session = AcademicSession::where('tenant_id', $tenant->id)->where('is_current', true)->first();

        AscSectionData::updateOrCreate(
            ['tenant_id' => $tenant->id, 'census_year' => $validated['census_year'], 'section' => $section],
            [
                'session_id' => $session?->id,
                'data' => $fields,
                'is_complete' => $isComplete,
                'updated_by' => auth()->id(),
            ]
        );

        return redirect()->route('asc.section.show', ['section' => strtolower($section), 'year' => $validated['census_year']])
            ->with('success', $isComplete ? "Section {$section} saved and marked complete." : "Section {$section} saved as incomplete. Complete all required fields before finalization.");
    }

    private function sanitize(array $submitted, array $definitions): array
    {
        $clean = [];

        foreach ($definitions as $key => $field) {
            $type = $field['type'] ?? 'text';
            $value = Arr::get($submitted, $key);

            if ($type === 'matrix') {
                $rows = $field['rows'] ?? [];
                $columns = $field['columns'] ?? [];
                foreach ($rows as $rowKey => $rowLabel) {
                    foreach ($columns as $columnKey => $columnLabel) {
                        $clean[$key][$rowKey][$columnKey] = max(0, (int) Arr::get($submitted, "{$key}.{$rowKey}.{$columnKey}", 0));
                    }
                }
                continue;
            }

            if ($type === 'checkboxes') {
                $allowed = array_keys($field['options'] ?? []);
                $clean[$key] = array_values(array_intersect($allowed, array_map('strval', (array) $value)));
                continue;
            }

            if ($type === 'number') {
                $clean[$key] = ($value === null || $value === '') ? null : max(0, (int) $value);
                continue;
            }

            if ($type === 'yes_no') {
                $clean[$key] = in_array($value, ['yes', 'no'], true) ? $value : null;
                continue;
            }

            $clean[$key] = is_scalar($value) ? trim((string) $value) : null;
        }

        return $clean;
    }

    private function isComplete(array $data, array $definitions): bool
    {
        foreach ($definitions as $key => $field) {
            if (!($field['required'] ?? false)) {
                continue;
            }

            $value = $data[$key] ?? null;
            if (($field['type'] ?? '') === 'matrix') {
                if (!is_array($value)) {
                    return false;
                }
                continue;
            }

            if ($value === null || $value === '') {
                return false;
            }
        }

        return true;
    }
}
