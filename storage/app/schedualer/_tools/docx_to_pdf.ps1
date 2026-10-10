# Cập nhật mục lục + số trang trong file Word rồi xuất PDF bằng Microsoft Word.
# Dùng: powershell -ExecutionPolicy Bypass -File docx_to_pdf.ps1 "<đường dẫn .docx>"
param([Parameter(Mandatory = $true)][string]$Docx)

$docx = (Resolve-Path $Docx).Path
$pdf = [System.IO.Path]::ChangeExtension($docx, '.pdf')
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $doc = $word.Documents.Open($docx, $false, $false)
    foreach ($toc in $doc.TablesOfContents) { $toc.Update() }
    $doc.Fields.Update() | Out-Null
    foreach ($sec in $doc.Sections) {
        foreach ($hf in @($sec.Headers, $sec.Footers)) {
            foreach ($i in 1..3) { $hf.Item($i).Range.Fields.Update() | Out-Null }
        }
    }
    $doc.Save()
    # 17 = wdExportFormatPDF; tạo bookmark theo Heading để PDF có mục lục bên trái
    $doc.ExportAsFixedFormat($pdf, 17, $false, 0, 0, 1, 1, 0, $true, $true, 1)
    "Trang: " + $doc.ComputeStatistics(2)
    $doc.Close(0)
    "Đã tạo $pdf"
} finally {
    $word.Quit()
}
