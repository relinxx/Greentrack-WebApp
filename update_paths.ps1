$htmlFiles = Get-ChildItem -Path "public_html" -Filter "*.html"

foreach ($file in $htmlFiles) {
    $content = Get-Content -Path $file.FullName -Raw
    
    # Update CSS paths
    $content = $content -replace 'href="css/', 'href="../css/'
    
    # Update JS paths
    $content = $content -replace 'src="js/', 'src="../js/'
    
    # Update image paths
    $content = $content -replace 'src="images/', 'src="../images/'
    $content = $content -replace 'src="img/', 'src="../img/'
    
    # Write the updated content back to the file
    Set-Content -Path $file.FullName -Value $content
}

Write-Host "All HTML files updated successfully!" 