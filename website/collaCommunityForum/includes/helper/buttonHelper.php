<?php
function renderButton($text, $class = "redeemButton", $isClickable = true)
{
    $attributes = "";

    //add disabled attribute if the button is not clickable
    if (!$isClickable) {
        $attributes = " disabled";
    }

    return "<button class=\"$class\"$attributes>$text</button>";
}

function encode($value) {
    return htmlentities($value);
}

// Generate <input type='hidden'>
function html_hidden($key, $attr = '') {
    $value ??= encode($GLOBALS[$key] ?? '');
    echo "<input type='hidden' id='$key' name='$key' value='$value' $attr>";
}

function html_hidden2($key, $value = '', $attr = '') {
    $value = empty($value) ? encode($GLOBALS[$key] ?? '') : encode($value);
    echo "<input type='hidden' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='text'>
function html_text($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='password'>
function html_password($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='password' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='number'>
function html_number($key, $min = '', $max = '', $step = '', $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='number' id='$key' name='$key' value='$value'
                 min='$min' max='$max' step='$step' $attr>";
}

// Generate <input type='search'>
function html_search($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='search' id='$key' name='$key' value='$value' $attr>";
}

// Generate <textarea>
function html_textarea($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<textarea id='$key' name='$key' $attr>$value</textarea>";
}

// Generate <input type='date'>
function html_date($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='date' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='tel'>
function html_tel($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='tel' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='email'>
function html_email($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='email' id='$key' name='$key' value='$value' $attr>";
}

// Generate <input type='submit'>
function html_submit($key, $value, $attr = '') {
    echo "<input type='submit' id='$key' name='$key' value='$value' $attr>";
}

function html_a($href, $text, $attr = '') {
    echo "<a href='$href' $attr>$text</a>";
}
function getPaymentUrl($method) {
    return "/controllers/otherPaymentController.php?method=" . urlencode($method);
}

// Function to generate <input type='hidden'>
function html_hiddens($key, $value, $attr = '') {
    $value = encode($value); // Optional encoding
    echo "<input type='hidden' id='$key' name='$key' value='$value' $attr>";
}

// Function to generate <input type='text'>
function html_texts($key, $placeholder, $value = '', $attr = '') {
    $value = encode($value ?? $GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' placeholder='$placeholder' $attr>";
}

// Function to generate <input type='email'>
function html_emails($key, $placeholder, $value = '', $attr = '') {
    $value = encode($value ?? $GLOBALS[$key] ?? '');
    echo "<input type='email' id='$key' name='$key' value='$value' placeholder='$placeholder' $attr>";
}

// Function to generate <input type='number'>
function html_numbers($key, $placeholder, $value = '', $attr = '') {
    $value = encode($value ?? $GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' placeholder='$placeholder' $attr>";
}

// Function to generate <input type='text' for expiry date (MM/YY format)>
function html_expirys($key, $placeholder, $value = '', $attr = '') {
    $value = encode($value ?? $GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' placeholder='$placeholder' maxlength='5' $attr>";
}

// Function to generate <select> for bank selection
function html_select($key, $options = [], $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<select id='$key' name='$key' $attr>";
    echo "<option value=''>Select a Bank...</option>"; // Default option
    foreach ($options as $option) {
        $selected = ($value === $option) ? 'selected' : '';
        echo "<option value='$option' $selected>$option</option>";
    }
    echo "</select>";
}


// Function to generate <input type='password'>
function html_passwords($key, $placeholder, $value = '', $attr = '') {
    $value = encode($value ?? $GLOBALS[$key] ?? '');
    echo "<input type='password' id='$key' name='$key' value='$value' placeholder='$placeholder' $attr>";
}
// Generate <input type='tel'>
function html_tels($key, $placeholder = '', $value = '', $attr = '') {
    $value = encode($GLOBALS[$key] ?? $value); // Sanitize and encode value
    echo "<input type='tel' id='$key' name='$key' value='$value' placeholder='$placeholder' $attr>";
}
?>