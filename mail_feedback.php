<?php
header("Content-Type: application/json; charset=utf-8");

function response($result) {
  echo json_encode($result, JSON_UNESCAPED_UNICODE);
  exit();
}

function field($key, $max = 500) {
  $v = isset($_POST[$key]) ? trim((string)$_POST[$key]) : "";
  if (function_exists("mb_substr")) {
    if (mb_strlen($v, "UTF-8") > $max) $v = mb_substr($v, 0, $max, "UTF-8");
  } elseif (strlen($v) > $max) {
    $v = substr($v, 0, $max);
  }
  return $v;
}

function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  response(["success" => false, "message" => "Не корректный метод отправки формы"]);
}

$name    = field("name", 120);
$phone   = field("phone", 40);
$email   = field("email", 120);
$message = field("message", 4000);

if ($name === "" || $phone === "" || $message === "") {
  response(["success" => false, "message" => "Одно или несколько полей заполнено неправильно!"]);
}

$body = "<p>Имя: " . h($name) . "</p>"
  . "<p>Телефон: " . h($phone) . "</p>"
  . "<p>Почта: " . h($email !== "" ? $email : "не указан") . "</p>"
  . "<p>Сообщение: " . nl2br(h($message)) . "</p>";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=utf-8\r\n";
$headers .= "From: Finger Food <mail@delivery.ru>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$mail_status = @mail("encher77@mail.ru", "Отзыв с сайта", $body, $headers);

response([
  "success" => true,
  "message" => "Успешно обработано!",
  "mailStatus" => $mail_status,
]);
