<?php
/*
@see https://www.owasp.org/index.php/PHP_CSRF_Guard

controlla che la richiesta sia stata inviata da una form generata dal nostro server e non da un client qualsiasi

le form sicure contengono una firma da noi emessa, sul server si tiene un registro delle firme valide
se la firma è registrata, viene eliminata ed emessa una ulteriore
se la firma non è (più) valida, la form è stata generara da un'altro sistema
*/

/*
an abstraction over how session variables are stored. Replace them if you don't use native PHP sessions.
*/
function session_set($key, $value) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION[$key] = $value;
}
function session_delete($key) {
    unset($_SESSION[$key]);
}
function session_get($key) {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
}



/* uso:
        if (!isset($_POST['CSRF_form_id']) or !isset($_POST['CSRF_token'])) {
            trigger_error("No CSRF_form_id found,  invalid request.", E_USER_ERROR);
        }
        $name = $_POST['CSRF_form_id'];
        $token = $_POST['CSRF_token'];
        if (!csrfguard_validate_token($name, $token)) {
            trigger_error("Invalid CSRF token.", E_USER_ERROR);
        }
*/
function csrfguard_generate() {
    $unique_form_name = "CSRFGuard_" . mt_rand(0, mt_getrandmax());
    $token            = csrfguard_generate_token($unique_form_name);
    $html_hidden = "
    <input type='hidden' name='CSRF_form_id' value='{$unique_form_name}' />
    <input type='hidden' name='CSRF_token' value='{$token}' /> ";
    return [
        $unique_form_name,
        $token,
        $html_hidden
    ];
}

/*
The generate function, creates a random secure one-time CSRF token.
If SHA512 is available, it is used, otherwise a 512 bit random string in the same format is generated.
This function also stores the generated token under a unique name in session variable.
*/
function csrfguard_generate_token($unique_form_name) {
    //if (function_exists("hash_algos") && in_array("sha512", hash_algos())) {
          $token = hash("sha512", mt_rand(0, mt_getrandmax()));
    //} else {
    //    $token = string_random($l=128);
    //}
    session_set($unique_form_name, $token);
    return $token;
}

/*
The validate function, checks under the unique name for the token. There are three states:

- Sessions not active: validate fails
- Token found but not the same, or token not found: validation fails
- Token found and the same: validation succeeds
Either case, this function removes the token from sessions, ensuring one-timeness.
*/
function csrfguard_validate_token($unique_form_name, $token_value) {
    $token = session_get($unique_form_name);
    if ($token === false) {
        return false;
    } elseif ($token === $token_value) {
        $result = true;
    } else {
        $result = false;
    }
    // ensuring one-timeness
    session_delete($unique_form_name);
    return $result;
}



/*
// genera una stringa random della lunghezza specificata
function string_random($l=128) {
    $token = '';
    for ($i = 0; $i < $l; ++$i) {
        $r = mt_rand(0, 35);
        if ($r < 26) {
            $c = chr(ord('a') + $r);//alpha
        } else {
            $c = chr(ord('0') + $r - 26);//numeric
        }
        // alpha uppecase r=range(0,25)
        // $c = chr(ord('A') + $r);//alpha
        $token .= $c;
    }
    return $token;
}*/

/*
// uso:

//receives a portion of html data, finds all <form> occurrences and adds two hidden fields to them: CSRF_form_id and CSRF_token.
//If any of these forms has an attribute or value 'nocsrf', the addition won't be performed (note that using default inject and detect breaks with this).
function csrfguard_replace_forms($form_data_html) {
    $count = preg_match_all("/<form(.*?)>(.*?)<\\/form>/is", $form_data_html, $matches, PREG_SET_ORDER);
    if (is_array($matches)) {
        foreach ($matches as $m) {
            if (strpos($m[1], "nocsrf") !== false) {continue;}

            list($unique_form_name, $token, $html_hidden ) = csrfguard_generate();


            $form_data_html = str_replace($m[0],
                "<form{$m[1]}>
                    $html_hidden
                    {$m[2]}
                </form>",
                $form_data_html
            );
        }
    }
    return $form_data_html;
}
function csrfguard_inject() {
    $data = ob_get_clean();
    $data = csrfguard_replace_forms($data);
    echo $data;
}
function csrfguard_start() {
    if (count($_POST)) {
        if (!isset($_POST['CSRF_form_id']) or !isset($_POST['CSRF_token'])) {
            trigger_error("No CSRF_form_id found,  invalid request.", E_USER_ERROR);
        }
        $name = $_POST['CSRF_form_id'];
        $token = $_POST['CSRF_token'];
        if (!csrfguard_validate_token($name, $token)) {
            trigger_error("Invalid CSRF token.", E_USER_ERROR);
        }
    }
    ob_start();
    // adding double quotes for "csrfguard_inject" to prevent:
    register_shutdown_function("csrfguard_inject");
}
csrfguard_start();
*/
