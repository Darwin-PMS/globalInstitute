<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Trainer;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::with(['course', 'module', 'teacher']);
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }
        $quizzes = $query->latest()->paginate(20)->withQueryString();
        $courses = Course::orderBy('title')->get();
        return view('admin.quizzes.index', compact('quizzes', 'courses'));
    }

    public function create()
    {
        $courses = Course::orderBy('title')->get();
        $modules = CourseModule::orderBy('name')->get();
        $lessons = Lesson::orderBy('title')->get();
        $teachers = Trainer::orderBy('name')->get();
        return view('admin.quizzes.create', compact('courses', 'modules', 'lessons', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'teacher_id' => 'required|exists:trainers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string',
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'required|integer|min:1',
            'pass_marks' => 'required|integer|min:0',
            'total_marks' => 'required|integer|min:1',
            'status' => 'required|in:draft,published,archived',
        ]);

        $validated['is_published'] = $validated['status'] === 'published';
        Quiz::create($validated);
        return redirect()->route('admin.quizzes.index')->with('success', 'Quiz created successfully.');
    }

    public function edit(Quiz $quiz)
    {
        $quiz->load('questions.options');
        $courses = Course::orderBy('title')->get();
        $modules = CourseModule::orderBy('name')->get();
        $teachers = Trainer::orderBy('name')->get();
        return view('admin.quizzes.edit', compact('quiz', 'courses', 'modules', 'teachers'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'module_id' => 'nullable|exists:course_modules,id',
            'teacher_id' => 'required|exists:trainers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'instructions' => 'nullable|string',
            'time_limit' => 'nullable|integer|min:1',
            'max_attempts' => 'required|integer|min:1',
            'pass_marks' => 'required|integer|min:0',
            'total_marks' => 'required|integer|min:1',
            'status' => 'required|in:draft,published,archived',
        ]);

        $validated['is_published'] = $validated['status'] === 'published';
        $quiz->update($validated);
        return redirect()->route('admin.quizzes.index')->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();
        return redirect()->route('admin.quizzes.index')->with('success', 'Quiz deleted successfully.');
    }

    public function addQuestion(Quiz $quiz)
    {
        return view('admin.quizzes.add-question', compact('quiz'));
    }

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:mcq,true_false,short_answer',
            'marks' => 'required|integer|min:1',
            'options' => 'required|array|min:2',
            'options.*.text' => 'required|string',
            'options.*.is_correct' => 'boolean',
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

        return redirect()->route('admin.quizzes.edit', $quiz)->with('success', 'Question added successfully.');
    }

    public function destroyQuestion(Quiz $quiz, QuizQuestion $question)
    {
        $question->delete();
        return redirect()->route('admin.quizzes.edit', $quiz)->with('success', 'Question deleted.');
    }
}
