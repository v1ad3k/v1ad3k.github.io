<?php

error_reporting(E_ERROR | E_PARSE);

$load = $_GET['load'];
$save = $_POST['save'];


if($load){
	$read = [];

	if(file_exists('raw/' .substr($load, 0, 2))){
		$read['conf'] =  file_get_contents('raw/' . substr($load, 0, 2) . '/' . $load . '.json');
		$read['mod']  =  json_decode(file_get_contents('raw/' . substr($load, 0, 2) . '/' . $load . '.conf.json'),true)['mod'];
	}
	else{
		$read['conf'] = file_get_contents('settings/apk.json');
		$read['mod']  = '';
	}

	echo json_encode($read);
}
else if(isset($_GET['save'])){
	$short = substr($_POST['uid'], 0, 2);

	if($_POST['uid'] && $_POST['json']){
		if (!file_exists('raw/' .$short)) {
		    mkdir('raw/' .$short, 0777, true);
		}
		
		file_put_contents('raw/' . $short . '/' . $_POST['uid']. '.json', $_POST['json']);
		file_put_contents('raw/' . $short . '/' . $_POST['uid']. '.conf.json', json_encode(['mod'=>$_POST['mod']]));
	}
	else{
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
	}
}
else{
	
?>

<!DOCTYPE html>
<html>
<head>
	<title>Редактор настроек</title>
</head>
<body>

<style type="text/css">
	* {
	    box-sizing: border-box;
	    outline: none;
	}
	body{
		padding: 40px;
		font-family: sans-serif;
	}
	label{
		display: block;
		font-weight: 700;
		margin-bottom: 8px;
	}
	input,
	textarea,
	select{
		width: 100%;
		padding: 10px;
	}
	button{
		padding: 10px;
	}
	form > * + *{
		margin-top: 30px;
	}
</style>

<form method="post" action="" id="form">
	<div>
		<label>Ваша ссылка</label>
		<input type="text" readonly="" name="save" id="uid">
	</div>
	<div>
		<label>Плагины</label>
		<select name="mod" id="mod">
			<option value="">Все</option>
			<option value="o">Онлайн</option>
			<option value="s">Клубничка</option>
		</select>
	</div>
	<div>
		<label>Настройки</label>
		<textarea id="value" name="value" rows="30"></textarea>
	</div>
	
	<button type="submit">Сохранить</button>
</form>

<script type="text/javascript">
	function hash(len){
		var ALPHABET  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    var ID_LENGTH = len || 8;

	    var id = '';

	    for (var i = 0; i < ID_LENGTH; i++) {
	        id += ALPHABET.charAt(Math.floor(Math.random() * ALPHABET.length));
	    }

	    return id;
	}



	let uid = localStorage.getItem('settings_uid');

	if(!uid){
		uid = hash(5)

		localStorage.setItem('settings_uid', uid)
	}

	document.getElementById('uid').value = 'https://bwa.to/' + uid

	fetch('/kit.php?load=' + uid).then((response) => response.text()).then(str=>{
		let json = JSON.parse(str)

		document.getElementById('value').value = json.conf || ''
		document.getElementById('mod').value = json.mod || ''
	}).catch(()=>{
		alert('ошибка')
	})

	document.getElementById('form').addEventListener("submit", (e) => {
		let json = document.getElementById('value').value

		e.preventDefault()

		try{
			JSON.parse(json)

			let formData = new FormData()
				formData.append('uid', uid)
				formData.append('json', json)
				formData.append('mod', document.getElementById('mod').value)

			fetch('/kit.php?save',{
			    method: "POST",
			    body: formData
			})
			.then((response)=>{
				if(response.ok) return response.text();  

				throw new Error('Не удалось сохранить настройки');
			 })  
			.then(()=>{
				alert('Сохранено')
			})
			.catch((e)=>{
				alert(e.message)
			})
		}
		catch(e){
			alert('Ошибка: ' + e.message)
		}
	})
</script>

</body>
</html>

<?php
	}
?>