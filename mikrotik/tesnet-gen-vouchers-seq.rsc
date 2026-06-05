# TesNet — generate 100 voucher users per profile (500 total)
# Upload to router Files, then: /import file-name=tesnet-gen-vouchers-seq.rsc
# Or paste into Winbox: System → Scripts → + → Source

/system script remove [find name=tesnet-gen-vouchers-seq]

/system script add name=tesnet-gen-vouchers-seq policy=read,write,test,policy,password source={
:local n 0
:for n from=1 to=100 do={
  :local num $n
  :if ($n < 10) do={ :set num ("00" . $n) }
  :if ($n >= 10 && $n < 100) do={ :set num ("0" . $n) }
  :local c ("TNQS" . $num)
  /ip hotspot user add name=$c password=$c profile=Quick_Surf_1GB server=all limit-bytes-total=1073741824 comment=voucher-Quick_Surf_1GB disabled=no
}
:put "Quick_Surf_1GB: 100 codes (TNQS001-TNQS100)"

:for n from=1 to=100 do={
  :local num $n
  :if ($n < 10) do={ :set num ("00" . $n) }
  :if ($n >= 10 && $n < 100) do={ :set num ("0" . $n) }
  :local c ("TNSC" . $num)
  /ip hotspot user add name=$c password=$c profile=Student_Choice_3GB server=all limit-bytes-total=3221225472 comment=voucher-Student_Choice_3GB disabled=no
}
:put "Student_Choice_3GB: 100 codes (TNSC001-TNSC100)"

:for n from=1 to=100 do={
  :local num $n
  :if ($n < 10) do={ :set num ("00" . $n) }
  :if ($n >= 10 && $n < 100) do={ :set num ("0" . $n) }
  :local c ("TNBB" . $num)
  /ip hotspot user add name=$c password=$c profile=Big_Bundle_7GB server=all limit-bytes-total=7516192768 comment=voucher-Big_Bundle_7GB disabled=no
}
:put "Big_Bundle_7GB: 100 codes (TNBB001-TNBB100)"

:for n from=1 to=100 do={
  :local num $n
  :if ($n < 10) do={ :set num ("00" . $n) }
  :if ($n >= 10 && $n < 100) do={ :set num ("0" . $n) }
  :local c ("TNHV" . $num)
  /ip hotspot user add name=$c password=$c profile=Heavy_User_15GB server=all limit-bytes-total=16106127360 comment=voucher-Heavy_User_15GB disabled=no
}
:put "Heavy_User_15GB: 100 codes (TNHV001-TNHV100)"

:for n from=1 to=100 do={
  :local num $n
  :if ($n < 10) do={ :set num ("00" . $n) }
  :if ($n >= 10 && $n < 100) do={ :set num ("0" . $n) }
  :local c ("TNHL" . $num)
  /ip hotspot user add name=$c password=$c profile=Hostel_Legend_45GB server=all limit-bytes-total=48318382080 comment=voucher-Hostel_Legend_45GB disabled=no
}
:put "Hostel_Legend_45GB: 100 codes (TNHL001-TNHL100)"
:put "Done — 500 voucher users created."
}

/system script run tesnet-gen-vouchers-seq
