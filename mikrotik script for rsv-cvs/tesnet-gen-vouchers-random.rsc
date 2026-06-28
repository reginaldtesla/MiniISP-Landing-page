# TesNet voucher codes: TN + 5 digits + 5 letters + 4 digits + 3 letters
# Example: TN56457VJHVJ4578JGF
# Upload to router Files → /import file-name=tesnet-gen-vouchers-random.rsc
# Or paste each :for block into terminal (multi-line).

:local digits "0123456789"
:local letters "ABCDEFGHIJKLMNOPQRSTUVWXYZ"

# --- Quick Surf 1GB (run this block) ---
:local n 0
:for n from=1 to=100 do={
  :local code ""
  :local tries 0
  :while ([:len $code] = 0) do={
    :set tries ($tries + 1)
    :if ($tries > 100) do={ :error "Could not generate unique code" }
    :local c "TN"
    :local i 0
    :for i from=1 to=5 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :for i from=1 to=4 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=3 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :if ([:len [/ip hotspot user find name=$c]] = 0) do={
      /ip hotspot user add name=$c password=$c profile=Quick_Surf_1GB server=all limit-bytes-total=1073741824 comment=voucher-Quick_Surf_1GB disabled=no
      :set code $c
    }
  }
}
:put "Quick_Surf_1GB: 100 codes done"

# --- Student Choice 3GB ---
:local n 0
:for n from=1 to=100 do={
  :local code ""
  :local tries 0
  :while ([:len $code] = 0) do={
    :set tries ($tries + 1)
    :if ($tries > 100) do={ :error "Could not generate unique code" }
    :local c "TN"
    :local i 0
    :for i from=1 to=5 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :for i from=1 to=4 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=3 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :if ([:len [/ip hotspot user find name=$c]] = 0) do={
      /ip hotspot user add name=$c password=$c profile=Student_Choice_3GB server=all limit-bytes-total=3221225472 comment=voucher-Student_Choice_3GB disabled=no
      :set code $c
    }
  }
}
:put "Student_Choice_3GB: 100 codes done"

# --- Big Bundle 7GB ---
:local n 0
:for n from=1 to=100 do={
  :local code ""
  :local tries 0
  :while ([:len $code] = 0) do={
    :set tries ($tries + 1)
    :if ($tries > 100) do={ :error "Could not generate unique code" }
    :local c "TN"
    :local i 0
    :for i from=1 to=5 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :for i from=1 to=4 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=3 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :if ([:len [/ip hotspot user find name=$c]] = 0) do={
      /ip hotspot user add name=$c password=$c profile=Big_Bundle_7GB server=all limit-bytes-total=7516192768 comment=voucher-Big_Bundle_7GB disabled=no
      :set code $c
    }
  }
}
:put "Big_Bundle_7GB: 100 codes done"

# --- Heavy User 15GB ---
:local n 0
:for n from=1 to=100 do={
  :local code ""
  :local tries 0
  :while ([:len $code] = 0) do={
    :set tries ($tries + 1)
    :if ($tries > 100) do={ :error "Could not generate unique code" }
    :local c "TN"
    :local i 0
    :for i from=1 to=5 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :for i from=1 to=4 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=3 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :if ([:len [/ip hotspot user find name=$c]] = 0) do={
      /ip hotspot user add name=$c password=$c profile=Heavy_User_15GB server=all limit-bytes-total=16106127360 comment=voucher-Heavy_User_15GB disabled=no
      :set code $c
    }
  }
}
:put "Heavy_User_15GB: 100 codes done"

# --- Hostel Legend 45GB ---
:local n 0
:for n from=1 to=100 do={
  :local code ""
  :local tries 0
  :while ([:len $code] = 0) do={
    :set tries ($tries + 1)
    :if ($tries > 100) do={ :error "Could not generate unique code" }
    :local c "TN"
    :local i 0
    :for i from=1 to=5 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :for i from=1 to=4 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }
    :for i from=1 to=3 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }
    :if ([:len [/ip hotspot user find name=$c]] = 0) do={
      /ip hotspot user add name=$c password=$c profile=Hostel_Legend_45GB server=all limit-bytes-total=48318382080 comment=voucher-Hostel_Legend_45GB disabled=no
      :set code $c
    }
  }
}
:put "Hostel_Legend_45GB: 100 codes done"
