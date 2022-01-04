<?php
// SPDX-License-Identifier: GPL-2.0-only

require __DIR__."/../config.php";

$pdo = createPDO(PDO_PARAM);
$query = <<<SQL
	SELECT id, username, name, created_at FROM gt_groups
	WHERE
		tg_group_id NOT IN (-1001278544502, -1001226735471) AND
		username IS NOT NULL
	ORDER BY username ASC;
SQL;
$st = $pdo->prepare($query);
$st->execute();

?>
<!DOCTYPE html>
<html>
<head>
	<title>public-inbox listing</title>
	<style type="text/css">
		.chat-link {
			text-decoration: none;
		}
	</style>
</head>
<body>
<pre>
<?php while ($r = $st->fetch(PDO::FETCH_ASSOC)):

$uname = isset($r["username"]) ? e($r["username"]) : "<i>No Username</i>";
$title = e($r["name"])." on lore.teainside.org";
?>

* <?= e($r["created_at"]) ?> - <a class="chat-link" href="/chat.php?id=<?php echo e($r["id"]); ?>"><?= $uname; ?></a>
  <?= $title; ?>

<?php endwhile; ?>
</pre>
</body>
</html>
