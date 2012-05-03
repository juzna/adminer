all: adminer.php

adminer.php:
	php compile.php

editor.php: adminer.php
	php compile.php

clean:
	$(RM) adminer.php
	$(RM) editor.php

.PHONY: adminer.php editor.php clean
