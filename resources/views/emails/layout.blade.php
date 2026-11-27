<!DOCTYPE html>
<html lang="it">
<body style="margin:0;padding:24px;background:#f2f3f7;font-family:Arial,Helvetica,sans-serif;color:#212529;font-size:15px;line-height:1.6">
<div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #dfe2e8;border-radius:12px;overflow:hidden">
    <div style="height:6px;background:#00a9ea"></div>
    <div style="padding:28px">
    <img src="{{ asset('img/logo-effepi.png') }}" alt="Effepi Master" width="170" style="display:block;margin:0 0 22px;height:auto">
    @yield('content')
    <p style="margin:28px 0 0;color:#495057">Il team Effepi Telefonia</p>
    </div>
</div>
<p style="max-width:560px;margin:14px auto 0;color:#6b7280;font-size:12px">Effepi Telefonia Srls · Viale P.L. Nervi 164, 04100 Latina · {{ config('effepi.mail.backoffice') }}</p>
</body>
</html>
