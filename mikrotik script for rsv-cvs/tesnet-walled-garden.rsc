# TesNet hotspot — walled garden for pay + Paystack checkout (HTTPS)
# Run on MikroTik terminal, or: /import file-name=tesnet-walled-garden.rsc
#
# Fixes: net::ERR_CONNECTION_CLOSED on checkout.paystack.com before login.

# --- HTTP walled garden (hostname / redirect matching) ---
:foreach r in=[/ip hotspot walled-garden find comment~"TesNet|Paystack"] do={
  /ip hotspot walled-garden remove $r
}

/ip hotspot walled-garden add dst-host=pay.tesnet.xyz action=allow comment="TesNet Pay"
/ip hotspot walled-garden add dst-host=tesnet.xyz action=allow comment="TesNet marketing"
/ip hotspot walled-garden add dst-host=www.tesnet.xyz action=allow comment="TesNet www"
/ip hotspot walled-garden add dst-host=checkout.paystack.com action=allow comment="Paystack checkout"
/ip hotspot walled-garden add dst-host=standard.paystack.co action=allow comment="Paystack standard"
/ip hotspot walled-garden add dst-host=api.paystack.co action=allow comment="Paystack API"
/ip hotspot walled-garden add dst-host=js.paystack.co action=allow comment="Paystack JS"
/ip hotspot walled-garden add dst-host=paystack.com action=allow comment="Paystack root"
/ip hotspot walled-garden add dst-host=*.paystack.com action=allow comment="Paystack subdomains"
/ip hotspot walled-garden add dst-host=public-files-paystack-prod.s3.eu-west-1.amazonaws.com action=allow comment="Paystack static files"
/ip hotspot walled-garden add dst-host=fonts.googleapis.com action=allow comment="Paystack fonts"
/ip hotspot walled-garden add dst-host=fonts.gstatic.com action=allow comment="Paystack fonts static"

# --- IP walled garden (required for HTTPS before login on many RouterOS versions) ---
:foreach r in=[/ip hotspot walled-garden ip find comment~"TesNet|Paystack|resolved"] do={
  /ip hotspot walled-garden ip remove $r
}

/ip hotspot walled-garden ip add dst-host=pay.tesnet.xyz action=accept comment="TesNet Pay"
/ip hotspot walled-garden ip add dst-host=tesnet.xyz action=accept comment="TesNet marketing"
/ip hotspot walled-garden ip add dst-host=checkout.paystack.com action=accept comment="Paystack checkout"
/ip hotspot walled-garden ip add dst-host=standard.paystack.co action=accept comment="Paystack standard"
/ip hotspot walled-garden ip add dst-host=api.paystack.co action=accept comment="Paystack API"
/ip hotspot walled-garden ip add dst-host=js.paystack.co action=accept comment="Paystack JS"

# Resolve current IPs into the IP table (helps when CDN IPs change)
:local hosts {"pay.tesnet.xyz";"checkout.paystack.com";"standard.paystack.co";"api.paystack.co";"js.paystack.co"}
:foreach h in=$hosts do={
  :do {
    :local ip [:resolve $h]
    :if ([:len $ip] > 0) do={
      :if ([:len [/ip hotspot walled-garden ip find dst-address=$ip]] = 0) do={
        /ip hotspot walled-garden ip add dst-address=$ip action=accept comment=("resolved " . $h)
      }
    }
  } on-error={
    :put ("Could not resolve " . $h)
  }
}

:put "TesNet walled garden applied. Test checkout from Wi-Fi before login."
