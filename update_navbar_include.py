from pathlib import Path
import re
root = Path(r'c:/xampp/htdocs/urban-thread')
files = ['index.php','shop.php','about.php','contact.php','faq.php','returns.php','cart.php','checkout.php','account.php','card.php','admin.php','reg.PHP','product.php']
for fname in files:
    path = root / fname
    text = path.read_text(encoding='utf-8')
    if "$current_page = basename($_SERVER['PHP_SELF']);" not in text:
        if "<?php" in text:
            text = text.replace("<?php", "<?php\n$current_page = basename($_SERVER['PHP_SELF']);\n", 1)
        else:
            text = "<?php $current_page = basename($_SERVER['PHP_SELF']); ?>\n" + text
    new_text, count = re.subn(r'<nav class="navbar[\s\S]*?</nav>\s*', "<?php include 'includes/navbar.php'; ?>\n", text, count=1)
    if count == 0:
        raise RuntimeError(f'Nav block not found in {fname}')
    path.write_text(new_text, encoding='utf-8')
