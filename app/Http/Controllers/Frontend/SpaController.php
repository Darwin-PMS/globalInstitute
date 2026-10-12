<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Room;
use App\Models\SiteSetting;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SpaController extends Controller
{
    /**
     * The public marketing site lives on a real domain; canonical URLs always
     * point there even when the SPA is served from a local sub-directory.
     */
    private const CANONICAL_ORIGIN = 'https://globalinstitute.devaanshitsolution.in';

    private const HOME_TITLE = 'Global Institute & CAD CAM | AutoCAD, SolidWorks, CATIA & CNC Training in Jamshedpur';

    private const HOME_DESCRIPTION = 'Global Institute & CAD CAM, Gamharia (Jamshedpur), offers hands-on AutoCAD, SolidWorks, CATIA, Creo, NX CAD, Fusion 360 and CNC/VMC programming training for Diploma, B.Tech and ITI students with real industrial projects, interview prep and placement assistance. Open Mon-Sun, 8 AM-9 PM.';

    /**
     * Serve the SPA shell.
     *
     * The client app is rendered by JavaScript, but search engines and AI
     * crawlers need real HTML. So the raw shell is hydrated server-side with
     * crawlable content, a route-specific title/description and canonical URL,
     * plus a <base> tag so relative assets resolve on clean (hash-free) URLs.
     * The SPA then takes over seamlessly on load.
     */
    public function index(Request $request): Response
    {
        $file = public_path('index_spa.html');

        if (!file_exists($file)) {
            abort(404);
        }

        $html = file_get_contents($file);

        $routePath = '/' . trim((string) $request->route('any'), '/');
        [$page, $slug] = $this->routeContext($routePath);

        // The shell must always render, even if the database is unavailable:
        // fall back to the keyword-rich defaults and let the SPA hydrate.
        try {
            [$title, $description, $body] = $this->buildContent($page, $slug);
        } catch (\Throwable $e) {
            [$title, $description, $body] = [self::HOME_TITLE, self::HOME_DESCRIPTION, ''];
        }

        $status = $page === 'notFound' ? 404 : 200;
        $canonical = rtrim(self::CANONICAL_ORIGIN, '/') . ($routePath === '/' ? '/' : $routePath);
        $baseHref = rtrim($request->root(), '/') . '/';

        $html = str_replace(
            '<meta charset="utf-8"/>',
            '<meta charset="utf-8"/>'."\n    ".'<base href="'.e($baseHref).'"/>',
            $html
        );

        $html = str_replace('<main id="app"></main>', '<main id="app">'.$body.'</main>', $html);

        $html = preg_replace('/<title>.*?<\/title>/s', '<title>'.e($title).'</title>', $html, 1);

        $html = preg_replace('/<meta name="description" content="[^"]*"\/>/', '<meta name="description" content="'.e($description).'"/>', $html, 1);
        $html = preg_replace('/<link rel="canonical" href="[^"]*"\/>/', '<link rel="canonical" href="'.e($canonical).'"/>', $html, 1);
        $html = preg_replace('/<meta property="og:url" content="[^"]*"\/>/', '<meta property="og:url" content="'.e($canonical).'"/>', $html, 1);
        $html = preg_replace('/<meta property="og:title" content="[^"]*"\/>/', '<meta property="og:title" content="'.e($title).'"/>', $html, 1);
        $html = preg_replace('/<meta property="og:description" content="[^"]*"\/>/', '<meta property="og:description" content="'.e($description).'"/>', $html, 1);
        $html = preg_replace('/<meta name="twitter:title" content="[^"]*"\/>/', '<meta name="twitter:title" content="'.e($title).'"/>', $html, 1);
        $html = preg_replace('/<meta name="twitter:description" content="[^"]*"\/>/', '<meta name="twitter:description" content="'.e($description).'"/>', $html, 1);

        return response($html, $status)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Map a clean URL path to a logical page + optional slug.
     *
     * @return array{0: string, 1: string|null}
     */
    private function routeContext(string $path): array
    {
        if ($path === '/') {
            return ['home', null];
        }
        if ($path === '/courses') {
            return ['courses', null];
        }
        if (preg_match('#^/courses/([^/]+)$#', $path, $m)) {
            return ['courseDetail', $m[1]];
        }
        if (in_array($path, ['/faculty', '/facilities', '/about', '/admissions', '/gallery', '/contact'], true)) {
            return [ltrim($path, '/'), null];
        }

        // Custom CMS page — a single-segment slug backed by a published Page.
        if (preg_match('#^/([^/]+)$#', $path, $m)) {
            try {
                if (Page::where('slug', $m[1])->where('status', 'published')->exists()) {
                    return ['page', $m[1]];
                }
            } catch (\Throwable $e) {
                // Database unavailable — fall through to the not-found shell.
            }
        }

        return ['notFound', null];
    }

    /**
     * Build the crawlable title, meta description and on-page HTML for a route.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function buildContent(string $page, ?string $slug): array
    {
        $settings = SiteSetting::pluck('setting_value', 'setting_key');
        $siteName = $settings->get('site_name', 'Global Institute & CAD CAM');

        return match ($page) {
            'home' => [
                self::HOME_TITLE,
                self::HOME_DESCRIPTION,
                $this->homeBody($settings, $siteName),
            ],
            'courses' => [
                'Courses & Training Programs | '.$siteName,
                'Explore CAD/CAM, CNC programming, industrial skills and computer training programs at Global Institute & CAD CAM, Gamharia, Jamshedpur. Flexible batches and placement assistance.',
                '<h1>Training Programs at '.e($siteName).'</h1>'.$this->coursesListHtml().$this->napHtml($settings),
            ],
            'courseDetail' => $this->courseDetailContent($slug, $siteName),
            'faculty' => [
                'Our Faculty | '.$siteName,
                'Meet the experienced trainers and industry professionals who teach CAD/CAM, CNC and technical programs at Global Institute & CAD CAM, Jamshedpur.',
                '<h1>Our Faculty</h1>'.$this->facultyHtml().$this->napHtml($settings),
            ],
            'facilities' => [
                'Campus & Facilities | '.$siteName,
                'Industry-standard CAD labs, computer labs, CNC labs, workshops and classrooms at Global Institute & CAD CAM, Gamharia, Jamshedpur.',
                '<h1>Campus & Facilities</h1>'.$this->facilitiesHtml().$this->napHtml($settings),
            ],
            'about' => [
                'About Us | '.$siteName,
                'Global Institute & CAD CAM is an industry-oriented technical training institute in Gamharia, Jamshedpur, delivering hands-on CAD/CAM and CNC training with real projects and placement assistance.',
                '<h1>About '.e($siteName).'</h1>'.$this->aboutHtml($settings).$this->napHtml($settings),
            ],
            'admissions' => [
                'Admissions & Enrollment | '.$siteName,
                'Admission details, eligibility, batch timings and enrollment process for CAD/CAM, CNC and industrial training courses at Global Institute & CAD CAM, Jamshedpur.',
                '<h1>Admissions</h1>'.$this->admissionsHtml().$this->napHtml($settings),
            ],
            'gallery' => [
                'Gallery | '.$siteName,
                'Photo gallery of labs, workshops, classrooms and student training activities at Global Institute & CAD CAM, Gamharia, Jamshedpur.',
                '<h1>Gallery</h1><p>Photos from our CAD labs, CNC workshop, classrooms and student training sessions at '.e($siteName).' in Gamharia, Jamshedpur.</p>'.$this->napHtml($settings),
            ],
            'contact' => [
                'Contact Us | '.$siteName,
                'Contact Global Institute & CAD CAM in Gamharia, Jamshedpur. Call +91 87899 61355 or +91 83401 45191, open Monday to Sunday, 8:00 AM to 9:00 PM.',
                '<h1>Contact '.e($siteName).'</h1>'.$this->napHtml($settings).$this->coursesListHtml(),
            ],
            'page' => $this->pageContent($slug, $siteName),
            default => [
                'Page Not Found | '.$siteName,
                'The page you are looking for could not be found. Explore our courses or contact '.$siteName.' in Gamharia, Jamshedpur.',
                '<h1>Page Not Found</h1><p>The page you requested could not be found. Explore our courses below or contact us in Gamharia, Jamshedpur.</p>'.$this->coursesListHtml().$this->napHtml($settings),
            ],
        };
    }

    private function homeBody($settings, string $siteName): string
    {
        $description = $settings->get('site_description')
            ?: 'Industry-oriented CAD/CAM and technical training institute in Gamharia, Jamshedpur.';

        return ''.
            '<h1>'.e($siteName).' &mdash; Industrial Training in Gamharia, Jamshedpur</h1>'.
            '<p>'.e($description).'</p>'.
            '<section><h2>Software &amp; Skills You Will Learn</h2>'.
            '<p>AutoCAD, SolidWorks, CATIA, Creo, NX CAD and Fusion 360, plus 2D drafting and 3D CAD modeling, mechanical drafting, engineering drawing, sheet metal design, reverse engineering, CNC turning and VMC machining, industrial safety and quality, and office productivity tools.</p></section>'.
            '<section><h2>Who Can Join</h2>'.
            '<p>Diploma, B.Tech and ITI students, as well as working professionals who want practical, job-ready technical skills.</p></section>'.
            '<section><h2>Placement &amp; Career Support</h2>'.
            '<p>Real industrial projects, interview preparation, resume guidance and placement assistance for every student.</p></section>'.
            '<section><h2>Training Programs</h2>'.$this->coursesListHtml().'</section>'.
            '<section><h2>Frequently Asked Questions</h2>'.$this->faqHtml().'</section>'.
            '<section><h2>Visit Us</h2>'.$this->napHtml($settings).'</section>';
    }

    private function coursesListHtml(): string
    {
        $courses = Course::with('category')
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        if ($courses->isEmpty()) {
            return '';
        }

        $items = $courses->map(function (Course $c) {
            $fee = ($c->fees == null || (float) $c->fees <= 0) ? 'Contact us' : '₹'.number_format((float) $c->fees);
            $meta = array_filter([
                $c->category?->name,
                $c->duration ? 'Duration: '.$c->duration : null,
                'Fees: '.$fee,
            ]);

            return '<li><a href="/courses/'.e($c->slug).'">'.e($c->title).'</a>'.
                ' &mdash; '.e($c->short_description ?: $c->description ?: '').
                ($meta ? ' ('.e(implode(', ', $meta)).')' : '').
                '</li>';
        })->implode('');

        return '<ul>'.$items.'</ul>';
    }

    private function faqHtml(): string
    {
        $faqs = Faq::orderBy('sort_order')->get();

        if ($faqs->isEmpty()) {
            return '';
        }

        $items = $faqs->map(fn (Faq $f) =>
            '<dt>'.e($f->question).'</dt><dd>'.e($f->answer).'</dd>'
        )->implode('');

        return '<dl>'.$items.'</dl>';
    }

    private function facultyHtml(): string
    {
        $trainers = Trainer::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();

        if ($trainers->isEmpty()) {
            return '';
        }

        $items = $trainers->map(function (Trainer $t) {
            $detail = array_filter([
                $t->designation,
                $t->qualification,
                $t->experience ? $t->experience.'+ years experience' : null,
                $t->specialization,
            ]);

            return '<li><strong>'.e($t->name).'</strong>'.
                ($detail ? ' &mdash; '.e(implode(', ', $detail)) : '').
                ($t->bio ? '. '.e($t->bio) : '').
                '</li>';
        })->implode('');

        return '<ul>'.$items.'</ul>';
    }

    private function facilitiesHtml(): string
    {
        $rooms = Room::where('status', 'active')->orderBy('type')->orderBy('name')->get();

        if ($rooms->isEmpty()) {
            return '';
        }

        $items = $rooms->map(fn (Room $r) =>
            '<li>'.e($r->name).($r->capacity ? ' (capacity '.e($r->capacity).')' : '').'</li>'
        )->implode('');

        return '<ul>'.$items.'</ul>';
    }

    private function aboutHtml($settings): string
    {
        $description = $settings->get('site_description')
            ?: 'Industry-oriented CAD/CAM and technical training institute in Gamharia, Jamshedpur.';

        return '<p>'.e($description).'</p>'.
            '<p>We train Diploma, B.Tech and ITI students and working professionals in AutoCAD, SolidWorks, CATIA, Creo, NX CAD, Fusion 360 and CNC/VMC programming, with industry-standard labs, real industrial projects, interview preparation, resume guidance and placement assistance.</p>';
    }

    private function admissionsHtml(): string
    {
        return '<p>Admissions are open for CAD/CAM, CNC programming, industrial skills and computer training programs. Diploma, B.Tech and ITI students and working professionals can join.</p>'.
            '<p>Flexible batch timings are available across morning, afternoon and evening sessions. For eligibility, fees and the enrollment process, contact our office or submit an enquiry online.</p>';
    }

    private function napHtml($settings): string
    {
        $address = $settings->get('contact_address', 'Gamharia Lal Building, Lakshmi Narayan, Near Jamshedpur, Jharkhand 832108');
        $phone1 = $settings->get('contact_phone', '+91 87899 61355');
        $phone2 = $settings->get('contact_phone_2');
        $hours = $settings->get('business_hours', 'Mon – Sun, 8:00 AM – 9:00 PM');
        $email = $settings->get('contact_email', 'info@globalinstitute.edu.in');

        $html = '<address>'.e($address).'</address>';
        $html .= '<p>Phone: <a href="tel:'.e(preg_replace('/[^0-9+]/', '', $phone1)).'">'.e($phone1).'</a>';
        if ($phone2) {
            $html .= ' or <a href="tel:'.e(preg_replace('/[^0-9+]/', '', $phone2)).'">'.e($phone2).'</a>';
        }
        $html .= '</p>';
        $html .= '<p>Email: <a href="mailto:'.e($email).'">'.e($email).'</a></p>';
        $html .= '<p>Opening hours: '.e($hours).'</p>';

        return $html;
    }

    private function courseDetailContent(?string $slug, string $siteName): array
    {
        $course = $slug
            ? Course::with('category')->where('slug', $slug)->where('status', 'published')->first()
            : null;

        if (! $course) {
            return [
                'Course Not Found | '.$siteName,
                'The requested course could not be found. Explore our CAD/CAM and CNC training programs at Global Institute & CAD CAM, Jamshedpur.',
                '<h1>Course Not Found</h1><p>The course you requested could not be found.</p>'.$this->coursesListHtml(),
            ];
        }

        $fee = ($course->fees == null || (float) $course->fees <= 0) ? 'Contact us' : '₹'.number_format((float) $course->fees);
        $skills = is_array($course->skills) ? $course->skills : [];

        $body = '<h1>'.e($course->title).'</h1>';
        $body .= '<p>'.e($course->short_description ?: '').'</p>';
        $body .= $course->description ? '<p>'.e($course->description).'</p>' : '';
        $body .= $course->overview ? '<h2>Overview</h2><p>'.e($course->overview).'</p>' : '';

        $body .= '<h2>Course Details</h2><ul>';
        if ($course->category) {
            $body .= '<li>Category: '.e($course->category->name).'</li>';
        }
        if ($course->duration) {
            $body .= '<li>Duration: '.e($course->duration).'</li>';
        }
        $body .= '<li>Fees: '.e($fee).'</li>';
        if ($course->eligibility) {
            $body .= '<li>Eligibility: '.e($course->eligibility).'</li>';
        }
        $body .= '</ul>';

        if ($skills) {
            $body .= '<h2>Skills You Will Learn</h2><ul>';
            foreach ($skills as $skill) {
                $body .= '<li>'.e($skill).'</li>';
            }
            $body .= '</ul>';
        }

        if ($course->career_information) {
            $body .= '<h2>Career Opportunities</h2><p>'.e($course->career_information).'</p>';
        }

        $settings = SiteSetting::pluck('setting_value', 'setting_key');
        $body .= $this->napHtml($settings);

        $description = $course->seo_description
            ?: mb_substr(strip_tags($course->short_description ?: $course->description ?: $course->title), 0, 300);
        $title = $course->seo_title ?: ($course->title.' | '.$siteName);

        return [$title, $description, $body];
    }

    /**
     * Crawlable content for a custom CMS page (published Page by slug).
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function pageContent(?string $slug, string $siteName): array
    {
        $page = $slug
            ? Page::where('slug', $slug)->where('status', 'published')->first()
            : null;

        if (! $page) {
            return [
                'Page Not Found | '.$siteName,
                'The page you requested could not be found. Explore our courses or contact '.$siteName.' in Gamharia, Jamshedpur.',
                '<h1>Page Not Found</h1><p>The page you requested could not be found.</p>'.$this->coursesListHtml(),
            ];
        }

        $body = '<article>';
        if ($page->featured_image) {
            $body .= '<img src="'.e($page->featured_image).'" alt="'.e($page->title).'"/>';
        }
        $body .= '<h1>'.e($page->title).'</h1>';
        if ($page->excerpt) {
            $body .= '<p>'.e($page->excerpt).'</p>';
        }
        $body .= '<div>'.(string) $page->content.'</div>';
        $body .= '</article>';

        $description = $page->seo_description
            ?: ($page->excerpt ?: mb_substr(strip_tags((string) $page->content), 0, 300));
        $title = $page->seo_title ?: ($page->title.' | '.$siteName);

        return [$title, $description, $body];
    }

    public function submitEnquiry(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:20',
            'email'       => 'nullable|email|max:255',
            'course_slug' => 'nullable|string',
            'message'     => 'nullable|string|max:1000',
            'subject'     => 'nullable|string|max:255',
            'source'      => 'nullable|string|max:50',
        ]);

        $courseId = null;
        if (!empty($validated['course_slug']) && $validated['course_slug'] !== 'other') {
            $course = Course::where('slug', $validated['course_slug'])->first();
            if ($course) {
                $courseId = $course->id;
            }
        }

        $subject = $validated['subject'] ?? null;
        if ($courseId && !$subject) {
            $course = Course::find($courseId);
            $subject = $course->title ?? null;
        }

        Enquiry::create([
            'name'      => $validated['name'],
            'phone'     => $validated['phone'],
            'email'     => $validated['email'] ?? null,
            'course_id' => $courseId,
            'message'   => $validated['message'] ?? null,
            'subject'   => $subject,
            'source'    => $validated['source'] ?? 'website',
            'status'    => 'new',
        ]);

        return response()->json(['success' => true, 'message' => 'Enquiry submitted successfully!']);
    }

    public function onlineLearningShell()
    {
        return view('layouts.online-learning');
    }

    /**
     * The Live Class Room shell.
     *
     * Deliberately its own view and its own JS bundle rather than a page inside
     * the Online Learning SPA: the room is a self-contained application, which is
     * what allows it to be extracted later. Authorisation is not done here — the
     * shell only hands the room key to the client, and every room endpoint
     * re-checks membership on the session user.
     */
    public function classRoomShell(string $roomKey)
    {
        return view('online-learning.room', ['roomKey' => $roomKey]);
    }
}
