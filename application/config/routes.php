<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function my_autoloader($class)
{
    if (substr($class, 0, 9) == "MY_Addon_") {
        if (file_exists($file = APPPATH . 'core/' . $class . '.php')) {
            include $file;
        }
    }
}
spl_autoload_register('my_autoloader');

$route['default_controller'] = 'welcome/index';
$route['user/resetpassword/([a-z]+)/(:any)'] = 'site/resetpassword/$1/$2';
$route['admin/resetpassword/(:any)'] = 'site/admin_resetpassword/$1';
$route['admin/unauthorized'] = 'admin/admin/unauthorized';
$route['parent/unauthorized'] = 'parent/parents/unauthorized';
$route['student/unauthorized'] = 'user/user/unauthorized';
$route['teacher/unauthorized'] = 'teacher/teacher/unauthorized';
$route['accountant/unauthorized'] = 'accountant/accountant/unauthorized';
$route['librarian/unauthorized'] = 'librarian/librarian/unauthorized';

$route['404_override'] = 'welcome/show_404';
$route['translate_uri_dashes'] = FALSE;
$route['cron/(:any)'] = 'cron/index/$1';

//======= front url rewriting==========
$route['page/(:any)'] = 'welcome/page/$1';
$route['read/(:any)'] = 'welcome/read/$1';
$route['online_admission'] = 'welcome/admission';
$route['examresult'] = 'welcome/examresult';
$route['frontend'] = 'welcome';
$route['cbseexam'] = 'welcome/cbseexam';
$route['online_course'] = 'course';


// Added the Mpesa controller routes
//======= MPESA controller routes =======
$route['demo/mpesa/validate'] = 'mpesa_controller/validate';
$route['demo/mpesa/confirm'] = 'mpesa_controller/confirm';
$route['demo/mpesa/reconcile'] = 'mpesa_controller/reconcile';
$route['demo/mpesa/timeout'] = 'mpesa_controller/timeout';
$route['demo/mpesa/results'] = 'mpesa_controller/results';
$route['demo/mpesa/process'] = 'mpesa_controller/process';
$route['demo/mpesa/dashboard'] = 'mpesa_controller/dashboard';
$route['demo/mpesa/view/(:num)'] = 'mpesa_controller/view/$1';
$route['demo/mpesa/mark_reconciled/(:num)'] = 'mpesa_controller/mark_reconciled/$1';
