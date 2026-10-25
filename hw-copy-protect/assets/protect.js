(function () {
	'use strict';
	var c = window.HWCP || {};
	var root = document.documentElement;
	var toastTimer;

	if (c.select) root.classList.add('hwcp-noselect');
	if (c.imageDrag) root.classList.add('hwcp-noimg');

	// Cho phép thao tác trong ô nhập liệu (form liên hệ, tìm kiếm, bình luận).
	function isEditable(el) {
		if (!el || !el.closest) return false;
		return !!el.closest('input, textarea, select, [contenteditable="true"]');
	}

	function toast() {
		if (!c.notice) return;
		var t = document.getElementById('hwcp-toast');
		if (!t) {
			t = document.createElement('div');
			t.id = 'hwcp-toast';
			document.body.appendChild(t);
		}
		t.textContent = c.notice;
		t.classList.add('show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { t.classList.remove('show'); }, 1800);
	}

	function block(e) {
		if (isEditable(e.target)) return;
		e.preventDefault();
		toast();
		return false;
	}

	if (c.rightClick) document.addEventListener('contextmenu', block);
	if (c.select) document.addEventListener('selectstart', block);
	if (c.copy) {
		document.addEventListener('copy', block);
		document.addEventListener('cut', block);
	}
	if (c.imageDrag) {
		document.addEventListener('dragstart', function (e) {
			if (e.target && e.target.tagName === 'IMG') block(e);
		});
	}

	if (c.shortcuts) {
		document.addEventListener('keydown', function (e) {
			var k = (e.key || '').toLowerCase();
			var mod = e.ctrlKey || e.metaKey;
			if (k === 'f12') return block(e);
			if (mod && e.shiftKey && (k === 'i' || k === 'j' || k === 'c')) return block(e);
			if (mod && (k === 'u' || k === 's' || k === 'p')) return block(e);
			if (mod && (k === 'c' || k === 'x' || k === 'a') && !isEditable(e.target)) return block(e);
		});
	}
})();
