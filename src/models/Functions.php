<?php

namespace optima\models;

use Yii;
use yii\base\Model;
use yii\base\ViewNotFoundException;
use yii\helpers\Url;
use linslin\yii2\curl;
class Functions extends Model
{

    public static function directory()
    {
        $webroot = Yii::getAlias('@webroot');
        if (!is_dir($webroot . '/uploads/'))
            mkdir($webroot . '/uploads/');
        if (!is_dir($webroot . '/uploads/temp/'))
            mkdir($webroot . '/uploads/temp/');
        return $webroot . '/uploads/temp/';
    }

    public static function deleteDirectory($dirname)
    {
        if (is_dir($dirname)) {
            $dir_handle = opendir($dirname);
        }

        if (!isset($dir_handle) || !$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname . "/" . $file)) {
                    unlink($dirname . "/" . $file);
                } else {
                    self::deleteDirectory($dirname . '/' . $file);
                }
            }
        }
        closedir($dir_handle);

        rmdir($dirname);

        return true;
    }

    public static function recaptcha($name = 'reCaptcha', $id = '')
    {
        $recaptcha_site_key = isset(Yii::$app->params['recaptcha_site_key']) ? Yii::$app->params['recaptcha_site_key'] : "6Le9fqsUAAAAAN2KL4FQEogpmHZ_GpdJ9TGmYMrT";
        $ret = "";
        $ret .= \himiklab\yii2\recaptcha\ReCaptcha2::widget([
            'name' => 'reCaptcha',
            'siteKey' => $recaptcha_site_key, // unnecessary is reCaptcha component was set up
            'widgetOptions' => ['class' => 'col-sm-offset-3', 'id' => $id],
        ]);
        return $ret;
    }

    public static function reCaptcha3($id = '', $class = 'col-sm-offset-3', $name = 'reCaptcha3')
    {
        $ret = "";
        $ret .= \himiklab\yii2\recaptcha\ReCaptcha3::widget([
            'name' => $name,
            'siteKey' => isset(Yii::$app->params['recaptcha_v3_site_key']) ? Yii::$app->params['recaptcha_v3_site_key'] : "6LfdYakZAAAAACxIv5KExmk7CsTGx7J_-KJdGvUX",
            'action' => 'captchaloaded',
            // 'widgetOptions' => ['class' => $class, 'id' => $id],
        ]);
        return $ret;
    }

    public static function siteSendEmail($object, $redirect_url = null)
    {
        $model = new ContactUs();
        $model->load(Yii::$app->request->get());
        $model->verifyCode = true;
        $model->reCaptcha = Yii::$app->request->get('reCaptcha');
        if ($model->reCaptcha3 = Yii::$app->request->get('reCaptcha3')) {
            $model->scenario = ContactUs::SCENARIO_V3;
        }

        if (isset($_GET['owner'])) {
            $model->owner = 1;
        }
        if (isset($_GET['friend_name']) && isset($_GET['friend_ser_name']) && isset($_GET['friend_email'])) {
            $message = '';

            $message .= 'Message: ' . $model->message;

            $model->message = "Friend's Name = " . $_GET['friend_name'] . "\r\n Friend's Ser Name = " . $_GET['friend_ser_name'] . "\r\n Friend's Email = " . $_GET['friend_email'] . "\r\n" . $message;
        }

        if (isset($_GET['morning_call']) || isset($_GET['afternoon_call'])) {
            if (isset($_GET['morning_call']) && !isset($_GET['afternoon_call'])) {
                $scedual_msg = 'Call me back in the morning';
            } elseif (isset($_GET['afternoon_call']) && !isset(($_GET['morning_call']))) {
                $scedual_msg = 'Call me back in the afternoon';
            } else {
                $scedual_msg = 'Call me back in the morning.<br>Call me back in the afternoon.';
            }
            $message = '';

            $message .= 'Message: ' . $model->message;

            $model->message = "Preferred time = " . $scedual_msg . "\r\n" . $message;
        }

        if (!$model->sendMail()) {
            /*if ($model->last_name == 'Request')
            {
                $model->reCaptcha = false;
                Yii::$app->session->setFlash('success', "Thank you for your message!");
            }*/
            $errors = 'Message not sent!';
            if (isset($model->errors) and count($model->errors) > 0) {
                $errs = array();
                foreach ($model->errors as $k => $err) {
                    $errs[] = $err[0];
                }
                $errors = implode(',', $errs);
            }

            Yii::$app->session->setFlash('failure', $errors);
            if (isset(Yii::$app->params['send_error_mails_to'])) {
                self::sendErrorMail($errors, Yii::$app->params['send_error_mails_to']);
            }
        } else {
            Yii::$app->session->setFlash('success', "Thank you for your message!");
            if ($redirect_url) {
                return $object->redirect($redirect_url);
            }
        }

        return $object->redirect(Yii::$app->request->referrer);
    }

    public static function sendErrorMail($model, $bcc = [])
    {
        $errors = 'Message not sent!';
        if (isset($model->errors) and count($model->errors) > 0) {
            $errs = array();
            foreach ($model->errors as $k => $err) {
                $errs[] = $err[0];
            }
            $errors = implode(',', $errs);
        }

        $message = "";
        $message .= 'Name : ' . $model->first_name . ' ' . $model->last_name . '<br>';
        $message .= 'Email : ' . $model->email . '<br>';
        $message .= 'Phone : ' . $model->phone . '<br>';
        $message .= 'Message : ' . $model->message . '<br><br>';
        $message .= 'Site : ' . Url::home(true) . '<br>';
        $message .= 'Url : ' . Yii::$app->request->referrer . '<br>';
        $message .= 'Errors : ' . $errors . '<br>';


        Yii::$app->mailer->compose()
            ->setFrom($model->email)
            ->setTo('support@optimasys.es')
            ->setBcc($bcc)
            ->setSubject('Leads Error')
            ->setHtmlBody($message)
            ->send();
    }

    public static function loadPageDynamically($object)
    {
        $slug = Yii::$app->request->get('slug', '');
        if ($slug) {
            $page_data = $object->view->params['page_data'] = Cms::getPage(['slug' => $slug, 'lang' => Yii::$app->language]);
            // } else {
            //     $page_data = $object->view->params['page_data'] = Cms::getPage(['slug' => Yii::$app->request->get('title'), 'lang' => strtoupper(Yii::$app->language)]);
        }

        // redirect if there is no page_data is available
        if (!isset($page_data) || empty(array_filter($page_data))) {
            $object->redirect('/404');
        }

        if (!empty($page_data['view_path'])) {
            try {
                return $object->render($page_data['view_path'], [
                    'page_data' => $page_data
                ]);
            } catch (ViewNotFoundException $error) {
                throw $error;
            }
        } elseif ($slug == '404') {
            return $object->render($slug, [
                'page_data' => $page_data
            ]);
        } else {
            return $object->render('page', [
                'page_data' => $page_data
            ]);
        }
    }

    public static function dynamicPage($object)
    {
        $cmsModel = Cms::Slugs('page');
        $url = explode('/', Yii::$app->request->url);
        $this_page = urldecode(end($url));
        $page_data = $object->view->params['page_data'] = Cms::pageBySlug(Yii::$app->request->get('title'));
        if (isset($cmsModel) and count($cmsModel) > 0)
            foreach ($cmsModel as $row) {
                // $cms_page_exists = true;
                if (isset($row['slug_all'][strtoupper(Yii::$app->language)]) and $row['slug_all'][strtoupper(Yii::$app->language)] == $this_page) {
                    $page_data = Cms::pageBySlug($this_page);
                    if (isset($page_data['custom_settings'][strtoupper(Yii::$app->language)]) and count($page_data['custom_settings'][strtoupper(Yii::$app->language)]) > 0)
                        foreach ($page_data['custom_settings'][strtoupper(Yii::$app->language)] as $custom_keys) {
                            if ($custom_keys['key'] == 'page_template') {
                                $page_template = $custom_keys['value'];
                            }
                            if ($custom_keys['key'] == 'custom_post_id') {
                                $custom_post_id = $custom_keys['value'];
                            }
                        }
                }
            }
        if (isset($page_template)) {
            try {
                if (isset($custom_post_id))
                    $custom_post_id = Cms::postTypes($custom_post_id);
                else
                    $custom_post_id = '';
                $object->view->params['page_data'] = $page_data;
                return $object->render($page_template, [
                    'page_data' => $page_data,
                    'custom_post_id' => $custom_post_id
                ]);
            } catch (ViewNotFoundException $e) {
                //die;
            }
        } elseif (isset($this_page) && is_file($this_page)) {
            return $object->render($this_page, [
                'page_data' => isset($page_data) ? $page_data : ''
            ]);
        } else {

            if (!array_filter($page_data)) {
                $page_data_404 = Cms::pageBySlug('404');

                if (!isset($page_data_404) || !isset($page_data_404['slug_all']['EN'])) {
                    die('Please create 404 page with sluge "404" in CMS');
                }
                $page_data = $object->view->params['page_data'] = Cms::pageBySlug('404');
                return $object->render('404', [
                    'page_data' => isset($page_data) ? $page_data : ''
                ]);
            }
            $object->view->params['page_data'] = $page_data;
            return $object->render('page', [
                'page_data' => isset($page_data) ? $page_data : ''
            ]);
        }
    }

    public static function getAgentsList($agent_name = '')
    {
        $url = Yii::$app->params['apiUrl'] .'properties/get-assigned-to-listing-agent&user_apikey=' . Yii::$app->params['api_key'].'&search_word='. $agent_name;
        $curl = new curl\Curl();
        $headers = Functions::getApiHeaders();
        $response = $curl->setHeaders($headers)->get($url);
        $response = json_decode($response);
        return $response;
    }

    public static function getCRMData($url, $cache = true, $fields = array(), $auth = false, $headers = array())
    {
        return Functions::getCurlData($url, $cache, $fields, $auth, $headers);
    }

    public static function getCurlData($url, $cache = true, $fields = array(), $auth = false, $headers = array())
    {
        $url = str_replace(' ', '%20', $url);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        if ($auth) {
            curl_setopt($curl, CURLOPT_USERPWD, "$auth");
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        if ($headers && is_array($headers) && count($headers) > 0) {            
            $curl_headers = array();
            foreach ($headers as $key => $value) {
                if (is_numeric($key)) {
                    $curl_headers[] = $value;
                } else {
                    $curl_headers[] = $key . ': ' . $value;
                }
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
        }

        if ($fields) {
            $fields_string = http_build_query($fields);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
        }

        $response = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header_string = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        $header_rows = explode(PHP_EOL, $header_string);
        //$header_rows = array_filter($header_rows, trim);
        $i = 0;
        foreach ((array) $header_rows as $hr) {
            $colonpos = strpos($hr, ':');
            $key = $colonpos !== false ? substr($hr, 0, $colonpos) : (int) $i++;
            $headers[$key] = $colonpos !== false ? trim(substr($hr, $colonpos + 1)) : $hr;
        }
        $j = 0;
        foreach ((array) $headers as $key => $val) {
            $vals = explode(';', $val);
            if (count($vals) >= 2) {
                unset($headers[$key]);
                foreach ($vals as $vk => $vv) {
                    $equalpos = strpos($vv, '=');
                    $vkey = $equalpos !== false ? trim(substr($vv, 0, $equalpos)) : (int) $j++;
                    $headers[$key][$vkey] = $equalpos !== false ? trim(substr($vv, $equalpos + 1)) : $vv;
                }
            }
        }
        //print_rr($headers);
        curl_close($curl);
        // echo $body;
        // die;
        return $body;
    }

    public static function array_map_assoc(callable $f, array $a)
    {
        return array_column(array_map($f, array_keys($a), $a), 1, 0);
    }


    public static function clean($string)
    {

        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    public static function getApiHeaders($headers_params = [])
    {
        $client_ip = $_SERVER["REMOTE_ADDR"];
        if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            $client_ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Connection' => 'keep-alive',
        ];
        
        if (!empty($headers_params) && is_array($headers_params)) {
            foreach ($headers_params as $key => $value) {
                if (is_array($value)) {
                    if (strtolower($key) === 'referer' && !empty($value)) {
                        $headers['Referer'] = isset($value[0]) ? $value[0] : 'https://my3.optima-crm.com/';
                        if (count($value) > 1) {
                            $headers['X-Client-Origins'] = implode(',', $value);
                        }
                    } else {
                        $headers[$key] = implode(',', $value);
                    }
                } else {
                    $headers[$key] = $value;
                }
            }
        }

        if (!empty($client_ip) && filter_var($client_ip, FILTER_VALIDATE_IP)) {
            $headers['x-forwarded-for'] = $client_ip;
        }

        return $headers;
    }
}
