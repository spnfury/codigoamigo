with(document.registro)
{
	onsubmit = function(e){
		e.preventDefault();
		ok = true;

		if(ok && pass.value!= confirm_password.value)
		{
			ok=false;
			$('#text_ayuda').text("Las contraseñas no coinciden. Intentalo de nuevo.");
			$('#text_ayuda').removeClass('hide');
			$('#pass').val("");
			$('#confirm_password').val("");
			pass.focus();
		}

		if(ok && pass.value == confirm_password.value)
		{
        	var txt = pass.value;
        	var tamany = txt.length;

			if (tamany < 8)
			{
				ok=false;
				$('#text_ayuda').text("La contraseña debe contener un mínimo de 8 caracteres. Intentalo de nuevo.");
				$('#text_ayuda').removeClass('hide');
				$('#pass').val("");
				$('#confirm_password').val("");
				pass.focus();
			}
		}

		if(ok){ submit(); }
	}
}
