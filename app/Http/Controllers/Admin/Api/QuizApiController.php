<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use Illuminate\Http\Request;

class QuizApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::with(['course', 'module', 'teacher']);
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);
        if ($request->filled('search')) $query->where('title', 'like', "%{$request->search}%");
        return response()->json($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'teacher_id' => 'nullable|exists:trainers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
            'total_marks' => 'required|integer|min:1',
            'passing_marks' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,published,closed',
        ]);
        if (isset($validated['status'])) {
            $validated['is_published'] = $validated['status'] === 'published';
        }
        $quiz = Quiz::create($validated);
        return response()->json(['success' => true, 'quiz' => $quiz, 'message' => 'Quiz created.']);
    }

    public function show(Quiz $quiz)
    {
        $quiz->load('questions.options');
        return response()->json($quiz);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'teacher_id' => 'nullable|exists:trainers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string',
            'duration' => 'nullable|integer|min:1',
            'total_marks' => 'required|integer|min:1',
            'passing_marks' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,published,closed',
        ]);
        if (isset($validated['status'])) {
            $validated['is_published'] = $validated['status'] === 'published';
        }
        $quiz->update($validated);
        return response()->json(['success' => true, 'message' => 'Quiz updated.']);
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();
        return response()->json(['success' => true, 'message' => 'Quiz deleted.']);
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:mcq,true_false,short_answer',
            'marks' => 'required|integer|min:1',
            'options' => 'required|array|min:2',
            'options.*.text' => 'required|string',
            'correct_option' => 'required|integer',
        ]);

        $question = $quiz->questions()->create([
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'marks' => $validated['marks'],
            'sequence' => $quiz->questions()->count() + 1,
        ]);

        foreach ($validated['options'] as $index => $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => $index == $validated['correct_option'],
                'sequence' => $index + 1,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Question added.']);
    }

    public function destroyQuestion(Quiz $quiz, QuizQuestion $question)
    {
        $question->delete();
        return response()->json(['success' => true, 'message' => 'Question deleted.']);
    }
}
