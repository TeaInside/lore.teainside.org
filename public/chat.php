<?php
// SPDX-License-Identifier: GPL-2.0-only

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
	header("Location: index.php");
	exit;
}

$limit = 30;
$order = "DESC";
$ltQuery = "";
if (isset($_GET["n"]) && is_numeric($_GET["n"])) {
	$n = (int) $_GET["n"];
	if ($n == -1)
		unset($_GET["n"]);
	else
		$ltQuery = " AND gt_messages.tg_msg_id < {$n}";
} else if (isset($_GET["p"]) && is_numeric($_GET["p"])) {
	$p = (int) $_GET["p"];
	if ($p == -1) {
		unset($_GET["p"]);
	} else {
		$order = "ASC";
		$ltQuery = " AND gt_messages.tg_msg_id > {$p}";
	}

}

require __DIR__."/../config.php";

$lastId = -1;
$firstId = -1;
$pdo = createPDO(PDO_PARAM);
$query = <<<SQL
	SELECT * FROM (
		SELECT
			gt_messages.id,
			gt_users.tg_user_id,
			gt_users.first_name,
			gt_users.last_name,
			gt_users.username,
			gt_messages.tg_msg_id,
			gt_messages.reply_to_tg_msg_id,
			gt_message_content.text,
			gt_messages.msg_type,
			gt_messages.has_edited_msg,
			gt_messages.is_forwarded_msg,
			gt_messages.is_deleted,
			gt_message_content.tg_date
		FROM gt_messages
		INNER JOIN gt_message_content ON gt_messages.id = gt_message_content.message_id
		INNER JOIN gt_senders ON gt_senders.id = gt_messages.sender_id
		INNER JOIN gt_sender_user ON gt_senders.id = gt_sender_user.sender_id
		INNER JOIN gt_users ON gt_users.id = gt_sender_user.user_id
		INNER JOIN gt_chats ON gt_chats.id = gt_messages.chat_id
		WHERE gt_chats.id = (
			SELECT gt_chat_group.chat_id FROM gt_chat_group
			INNER JOIN gt_groups ON gt_groups.id = gt_chat_group.group_id
			WHERE gt_groups.id = ? LIMIT 1
		) {$ltQuery}
		ORDER BY gt_messages.tg_msg_id {$order}
		LIMIT {$limit}
	) tmp ORDER BY tg_msg_id DESC;
SQL;
$st = $pdo->prepare($query);
$st->execute([$_GET["id"]]);
?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<style type="text/css">a{text-decoration: none;}</style>
</head>
<body>
<pre>
<?php

ob_start();
$i = 0;
while ($r = $st->fetch(PDO::FETCH_ASSOC)):

$lastId = (int) $r["tg_msg_id"];

if ($i++ === 0)
	$firstId = $lastId;

$name = e($r["first_name"].(isset($r["last_name"]) ? " ".$r["last_name"] : ""));

?>

<b><?= date("c", strtotime($r["tg_date"])); ?>, msg_id:<?= e($r["tg_msg_id"])?>, <?= $name ?> wrote:</b>
<?= e(wordwrap($r["text"], 80)); ?>


<?php endwhile;

if ($i === 0) {
	header("Location: /chat.php?id={$_GET["id"]}");
	exit;
}

$out = ob_get_clean();
ob_start(); ?><hr/>page: <a href="/chat.php?id=<?= e($_GET["id"]); ?>&n=<?= $lastId; ?>">next (older)</a><?php if ($firstId !== -1 && (isset($_GET["n"]) || isset($_GET["p"])) && $i === $limit) { ?> | <a href="/chat.php?id=<?= e($_GET["id"]); ?>&p=<?= $firstId; ?>">prev (newer)</a> | <a href="/chat.php?id=<?= e($_GET["id"]); ?>">latest</a><?php } ?><hr/><?php $paginator = ob_get_clean(); ?>
<?= $paginator; ?>
<?= $out; ?>
<?= $paginator; ?>
</body>
</html>
