$conn = New-Object -ComObject ADODB.Connection
try {
  $conn.Open("Provider=Microsoft.ACE.OLEDB.12.0;Data Source=C:\Users\QA2-Phongnh\Desktop\PM HC\Database_AD.accdb")
  $schema = $conn.OpenSchema(20)
  while (-not $schema.EOF) {
      if ($schema.Fields.Item("TABLE_TYPE").Value -eq "TABLE") {
          Write-Host $schema.Fields.Item("TABLE_NAME").Value
      }
      $schema.MoveNext()
  }
  $conn.Close()
} catch {
  Write-Host "Error:" $_
}
