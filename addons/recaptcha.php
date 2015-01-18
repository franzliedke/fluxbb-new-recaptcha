<?php

class addon_recaptcha extends flux_addon
{
    function register($manager)
    {
        if ($this->is_configured())
        {
            $manager->bind('register_validate', [$this, 'hook_register_validate']);
            $manager->bind('register_pre_submit', [$this, 'hook_register_pre_submit']);
        }
    }

    function is_configured()
    {
        global $pun_config;

        return !empty($pun_config['recaptcha_site_key']) && !empty($pun_config['recaptcha_secret_key']);
    }

    function hook_register_validate()
    {
        global $errors;

        if (!$this->verify_user_response())
        {
            $errors[] = 'Please prove that you are human.';
        }
    }

    function hook_register_pre_submit()
    {
        global $pun_config;

        $site_key = $pun_config['recaptcha_site_key'];

?>
        <div class="inform">
            <fieldset>
                <legend>Are you a human?</legend>
                <div class="infldset">
                    <p>Please prove that you're a human being.</p>
                    <script src='https://www.google.com/recaptcha/api.js'></script>
                    <div class="g-recaptcha" data-sitekey="<?php echo pun_htmlspecialchars($site_key) ?>"></div>
                </div>
            </fieldset>
        </div>
<?php
    }

    function verify_user_response()
    {
        global $pun_config;
        
        if (empty($_POST['g-recaptcha-response'])) return false;

        $secret = $pun_config['recaptcha_secret_key'];
        $response = $_POST['g-recaptcha-response'];
        $ip = get_remote_address();

        $query = "secret=$secret&response=$response&remoteip=$ip";
        $url = "https://www.google.com/recaptcha/api/siteverify?$query";

        $response = $this->send_request($url);

        return strpos($response, '"success": true') !== false;
    }

    function send_request($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
