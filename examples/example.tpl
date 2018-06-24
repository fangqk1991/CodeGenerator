
Normal: {$first}

For Array:
{foreach $arr as $index => $item}
    {$index}: '{$item}';
{/foreach}

For Map:
{foreach $map as $key => $item}
    {$key} => '{$item["name"]}';
{/foreach}

