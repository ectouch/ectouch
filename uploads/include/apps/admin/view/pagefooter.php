<script type="text/javascript">
function showImg(id, title){
	var _content = $('#'+id).html();
	art.dialog({
		id: 'showImg',
		padding: 0,
		title: title,
		content: _content,
		lock: false
	});
}
</script>
</body>
</html>