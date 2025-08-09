<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Team;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categorySlug = $request->get('category');

        $categoryId = null;
        if ($categorySlug) {
            $categoryId = \App\Models\Category::whereRaw('LOWER(name) = ?', [strtolower($categorySlug)])
                ->value('id');
        }

        $teams = Team::whereHas('surveys', function ($query) use ($categoryId) {
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
        })
            ->with([
                'surveys' => function ($query) use ($categoryId) {
                    if ($categoryId) {
                        $query->where('category_id', $categoryId);
                    }
                }
            ])
            ->get();

        return view('surveys.index', compact('teams', 'categorySlug'));
    }

    public function embed(Survey $survey)
    {
        $url = \App\Support\WilkerstatMetabase::dashboard(
            $survey->metabase_dashboard_id
        );
        return view('surveys.embed', compact('survey', 'url'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $path = $request->file('img_survey')->store('surveys', 'public');

        //$survey->img_survey = $path;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
