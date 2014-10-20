<?php

// redirect legacy url to symfony application
$base = dirname($_SERVER['SCRIPT_URL']);
header("Location: $base/forward");
