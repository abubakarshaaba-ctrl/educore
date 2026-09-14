<?php

namespace App\Http\Controllers;

use App\Services\AcademicRepositoryIngestionService;
use Illuminate\Http\Request;

class AcademicRepositoryIngestionController extends Controller
{
    public function index(AcademicRepositoryIngestionService $ingestion)
    {
        $this->guardAdmin();
        $preview = $ingestion->preview();

        return view('academic-repository.knowledge.ingestion', $preview);
    }

    public function store(Request $request, AcademicRepositoryIngestionService $ingestion)
    {
        $this->guardAdmin();
        $request->validate(['confirm' => ['accepted']]);

        $result = $ingestion->ingestAll();

        return redirect()
            ->route('academic-repository.knowledge.index')
            ->with('success', "Repository ingestion completed: {$result['created']} draft topic(s) created from {$result['sourceCount']} source(s); {$result['skipped']} source(s) skipped.");
    }

    private function guardAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Only school administrators can ingest repository content into the knowledge base.');
    }
}
