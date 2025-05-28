<!DOCTYPE html>
<html>
<head>
    <title>Redirigiendo a Webpay...</title>
</head>
<body onload="document.forms[0].submit();">
    <p>Redirigiendo al portal de pago...</p>
    <form action="{{ $url }}" method="POST">
        <input type="hidden" name="token_ws" value="{{ $token }}">
        <noscript>
            <p>JavaScript está desactivado. Presiona el botón para continuar.</p>
            <button type="submit">Pagar</button>
        </noscript>
    </form>
</body>
</html>
