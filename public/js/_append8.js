
/* ----------------------------------------------------------
   9. Routes
   ---------------------------------------------------------- */
Router.add('/dashboard', function() { DashboardPage(); });

function makeRoutes(key) {
    Router.add('/' + key, function(args) {
        if (!args || !args.length) { GenericListPage(pages[key]); }
        else if (args[0] === 'create') { GenericCreatePage(pages[key]); }
        else { GenericEditPage(pages[key], args[0]); }
    });
}
makeRoutes('courses');
makeRoutes('categories');
makeRoutes('teachers');
makeRoutes('students');
makeRoutes('batches');
makeRoutes('enquiries');
makeRoutes('lessons');
makeRoutes('online-classes');
makeRoutes('materials');
makeRoutes('assignments');
makeRoutes('quizzes');
makeRoutes('recordings');
makeRoutes('progress');

/* ----------------------------------------------------------
   10. Init
   ---------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', function() { AdminApp.init(); });
