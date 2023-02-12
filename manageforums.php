<?php
require('lib/common.php');

if ($userdata['powerlevel'] < 3) error('403', 'You have no permissions to do this!');

if (isset($_POST['savecat'])) {
	// save new/existing category
	$cid = $_GET['cid'];
	$title = $_POST['title'];
	$ord = (int)$_POST['ord'];
	if (!trim($title))
		error('400', 'Please enter a title for the category.');
	else {
		if ($cid == 'new') {
			$cid = result("SELECT MAX(id) FROM categories");
			if (!$cid) $cid = 0;
			$cid++;
			query("INSERT INTO categories (id,title,ord) VALUES (?,?,?)", [$cid, $title, $ord]);
		} else {
			$cid = (int)$cid;
			if (!result("SELECT COUNT(*) FROM categories WHERE id = ?", [$cid])) redirect('manageforums.php');
			query("UPDATE categories SET title = ?, ord = ? WHERE id = ?", [$title, $ord, $cid]);
		}
		redirect('manageforums.php?cid='.$cid);
	}
} elseif (isset($_POST['delcat'])) {
	// delete category
	$cid = (int)$_GET['cid'];
	query("DELETE FROM categories WHERE id = ?",[$cid]);

	redirect('manageforums.php');
} elseif (isset($_POST['saveforum'])) {
	// save new/existing forum
	$fid = $_GET['fid'];
	$cat = (int)$_POST['cat'];
	$title = $_POST['title'];
	$descr = $_POST['descr'];
	$ord = (int)$_POST['ord'];

	$minread = (int)$_POST['minread'];
	$minthread = (int)$_POST['minthread'];
	$minreply = (int)$_POST['minreply'];

	if (!trim($title))
		error('400', 'Please enter a title for the forum.');
	else {
		if ($fid == 'new') {
			$fid = result("SELECT MAX(id) FROM forums");
			if (!$fid) $fid = 0;
			$fid++;
			query("INSERT INTO forums (id,cat,title,descr,ord,minread,minthread,minreply) VALUES (?,?,?,?,?,?,?,?)",
				[$fid, $cat, $title, $descr, $ord, $minread, $minthread, $minreply]);
		} else {
			$fid = (int)$fid;
			if (!result("SELECT COUNT(*) FROM forums WHERE id = ?", [$fid]))
				redirect('manageforums.php');
			query("UPDATE forums SET cat=?, title=?, descr=?, ord=?, minread=?, minthread=?, minreply=? WHERE id=?",
				[$cat, $title, $descr, $ord, $minread, $minthread, $minreply, $fid]);
		}
		redirect('manageforums.php?fid='.$fid);
	}
} elseif (isset($_POST['delforum'])) {
	// delete forum
	$fid = (int)$_GET['fid'];
	query("DELETE FROM forums WHERE id = ?",[$fid]);
	redirect('manageforums.php');
}

$twig = twigloader();

if (isset($_GET['cid']) && $cid = $_GET['cid']) {
	// category editor
	if ($cid == 'new') {
		$cat = ['id' => 0, 'title' => '', 'ord' => 0];
	} else {
		$cid = (int)$cid;
		$cat = fetch("SELECT * FROM categories WHERE id=?",[$cid]);
	}

	echo $twig->render("manageforums_category.twig", [
		'cid' => $cid,
		'cat' => $cat
	]);
} elseif (isset($_GET['fid']) && $fid = $_GET['fid']) {
	// forum editor
	if ($fid == 'new') {
		$forum = [
			'id' => 0, 'cat' => 1, 'title' => '', 'descr' => '',
			'ord' => 0,
			'minread' => -1, 'minthread' => 1, 'minreply' => 1];
	} else {
		$fid = (int)$fid;
		$forum = fetch("SELECT * FROM forums WHERE id = ?",[$fid]);
	}
	$qcats = query("SELECT id,title FROM categories ORDER BY ord, id");
	$cats = [];
	while ($cat = $qcats->fetch())
		$cats[$cat['id']] = $cat['title'];

	$perms = [
		'minread' => 'Who can view',
		'minthread' => 'Who can make threads',
		'minreply' => 'Who can reply'
	];

	echo $twig->render("manageforums_forum.twig", [
		'fid' => $fid,
		'forum' => $forum,
		'cats' => $cats,
		'powerlevels' => $powerlevels,
		'perms' => $perms
	]);
} else {
	// main page -- category/forum listing

	$qcats = query("SELECT id,title FROM categories ORDER BY ord, id");
	$cats = [];
	while ($cat = $qcats->fetch())
		$cats[$cat['id']] = $cat;

	$qforums = query("SELECT f.id,f.title,f.cat FROM forums f LEFT JOIN categories c ON c.id=f.cat ORDER BY c.ord, c.id, f.ord, f.id");
	$forums = [];
	while ($forum = $qforums->fetch())
		$forums[$forum['id']] = $forum;

	$catlist = ''; $c = 1;
	foreach ($cats as $cat) {
		$catlist .= sprintf('<tr><td class="n%s"><a href="manageforums.php?cid=%s">%s</a></td></tr>', $c, $cat['id'], $cat['title']);
		$c = ($c == 1) ? 2 : 1;
	}

	$forumlist = ''; $c = 1; $lc = -1;
	foreach ($forums as $forum) {
		if ($forum['cat'] != $lc) {
			$lc = $forum['cat'];
			$forumlist .= sprintf('<tr class="c"><td>%s</td></tr>', $cats[$forum['cat']]['title']);
		}
		$forumlist .= sprintf('<tr><td class="n%s"><a href="manageforums.php?fid=%s">%s</a></td></tr>', $c, $forum['id'], $forum['title']);
		$c = ($c == 1) ? 2 : 1;
	}

	echo $twig->render("manageforums.twig", [
		'catlist' => $catlist,
		'forumlist' => $forumlist
	]);
}