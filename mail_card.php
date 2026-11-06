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

$name     = field("name", 120);
$phone    = field("phone", 40);
$email    = field("email", 120);
$comment  = field("comment", 2000);
$address  = field("address", 300);
$flat     = field("flat", 40);
$floor    = field("floor", 20);
$entrance = field("entrance", 20);
$zone     = field("zone", 80);
$payment  = field("payment", 40);
$delivery = isset($_POST["delivery"]) ? (int)$_POST["delivery"] : 0;

$items = json_decode(isset($_POST["items"]) ? (string)$_POST["items"] : "[]", true);
if (!is_array($items)) $items = [];

$lines = [];
$goods = 0;
foreach (array_slice($items, 0, 80) as $it) {
  $title = trim((string)($it["name"] ?? ""));
  if ($title === "") continue;
  $qty   = max(1, min(99, (int)($it["qty"] ?? 1)));
  $price = max(0, (int)($it["price"] ?? 0));
  $size  = trim((string)($it["size"] ?? ""));
  $sum   = $price * $qty;
  $goods += $sum;
  $sizeBit = $size !== "" ? " ({$size} см)" : "";
  $lines[] = h($title) . h($sizeBit) . " × {$qty} — {$sum} ₽";
}

if ($name === "" || $phone === "" || $address === "" || !$lines) {
  response(["success" => false, "message" => "Одно или несколько полей заполнено неправильно!"]);
}

$total = $goods + max(0, $delivery);
$dash = "не указано";
$orderHtml = implode("<br>", $lines);

$body = "<p>Имя: " . h($name) . "</p>"
  . "<p>Телефон: " . h($phone) . "</p>"
  . "<p>Почта: " . h($email !== "" ? $email : "не указан") . "</p>"
  . "<p>Адрес: " . h($address) . "</p>"
  . "<p>Подъезд: " . h($entrance !== "" ? $entrance : $dash) . "</p>"
  . "<p>Этаж: " . h($floor !== "" ? $floor : $dash) . "</p>"
  . "<p>Квартира: " . h($flat !== "" ? $flat : $dash) . "</p>"
  . "<p>Зона доставки: " . h($zone !== "" ? $zone : $dash) . "</p>"
  . "<p>Оплата: " . h($payment !== "" ? $payment : $dash) . "</p>"
  . "<p>Комментарий: " . nl2br(h($comment !== "" ? $comment : $dash)) . "</p>"
  . "<p>Заказ:<br>" . $orderHtml . "</p>"
  . "<p>Товары: {$goods} ₽</p>"
  . "<p>Доставка: " . ($delivery > 0 ? $delivery . " ₽" : "бесплатно") . "</p>"
  . "<p>Итого: {$total} ₽</p>";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=utf-8\r\n";
$headers .= "From: Finger Food <mail@delivery.ru>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$mail_status = @mail("encher77@mail.ru", "Новый заказ", $body, $headers);

response([
  "success" => true,
  "message" => "Успешно обработано!",
  "mailStatus" => $mail_status,
]);
