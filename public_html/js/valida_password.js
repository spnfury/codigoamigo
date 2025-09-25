with(document.nuevo_password)
{
	onsubmit = function(e){
		e.preventDefault();
		ok = true;

		if(ok && nueva_password.value != nueva_confirm_password.value)
		{
			ok=false;
			$('#text_ayuda').text("Las contraseñas no coinciden. Intentalo de nuevo.");
			$('#text_ayuda').removeClass('hide');
			$('#nueva_password').val("");
			$('#nueva_confirm_password').val("");
			nueva_password.focus();
		}

		if(ok && nueva_password.value == nueva_confirm_password.value)
		{
        	var txt = nueva_password.value;
        	var tamany = txt.length;

			if (tamany < 8)
			{
				ok=false;
				$('#text_ayuda').text("La contraseña debe contener un mínimo de 8 caracteres. Intentalo de nuevo.");
				$('#text_ayuda').removeClass('hide');
				$('#nueva_password').val("");
				$('#nueva_confirm_password').val("");
				nueva_password.focus();
			}
		}

		if(ok){ submit(); }
	}
}

