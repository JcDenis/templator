<?php

class templatorMedia extends dcMedia
{
	// limit to html files
    protected function isFileExclude(string $file): bool
    {
        return !preg_match('/\.html$/i', $file);
    }
}