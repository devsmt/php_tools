<?hh
namespace PCRE {
    use Exception;
    const string PCRE_CASELESS = 'i';
    const string PCRE_MULTILINE = 'm';
    const string PCRE_DOTALL = 's';
    const string PCRE_EXTENDED = 'x';
    const string PCRE_ANCHORED = 'A';
    const string PCRE_DOLLAR_ENDONLY = 'D';
    const string PCRE_UNGREEDY = 'U';
    const string PCRE_EXTRA = 'X';
    const string PCRE_UTF8 = 'u';
    const string PCRE_STUDY = 'S';
    function pcre_quote(string $text): string {
        return \preg_quote($text);
    }
    function pcre_match( string $regex, string $subject, string $options = '', int $offset = 0 ): ?PCREMatch {
        $match = [];
        $count = \preg_match(
            _pcre_compose($regex, $options),
            $subject,
            $match,
            \PREG_OFFSET_CAPTURE,
            $offset,
        );
        _pcre_check_last_error();
        return $count ? new PCREMatch($match) : new_null();
    }
    function pcre_match_all( string $regex, string $subject, string $options, int $offset = 0 ): array<PCREMatch> {
        $matches = [];
        \preg_match_all(
            _pcre_compose($regex, $options),
            $subject,
            $matches,
            \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE,
            $offset,
        );
        $f = function($match) {
            return new PCREMatch($match);
        };
        return map( $matches, $f );
    }
    function pcre_replace( string $regex, string $subject, string $replacement, ?int $limit = null, string $options = '' ): string {
        $result = \preg_replace(
            _pcre_compose($regex, $options),
            $replacement,
            $subject,
            $limit === null ? -1 : \max(0, $limit),
            );
        _pcre_check_last_error();
        if (!\is_string($result)) {
            throw new PCREException('preg_replace() failed');
        }
        return $result;
    }
    function pcre_split( string $regex, string $subject, ?int $limit = null, string $options = '' ): array<string> {
        $pieces = \preg_split(
            _pcre_compose($regex, $options),
            $subject,
            $limit === null ? -1 : max(1, $limit),
            );
        _pcre_check_last_error();
        if (!\is_array($pieces)) {
            throw new PCREException('preg_split() failed');
        }
        return $pieces;
    }
    final class PCREMatch {
        public function __construct(private array<arraykey, (string, int)> $match) {
            // A sub pattern will exist in $subPatterns if it didn't match
            // only if a later sub pattern matched.
            //
            // Example:
            //   match (a)(lol)?b against "ab"
            //   - ["ab", 0]
            //   - ["a", 0]
            //   match (a)(lol)?(b) against "ab"
            //   - ["ab", 0]
            //   - ["a", 0]
            //   - ["", -1]
            //   - ["b", 1]
            //
            // Remove those ones.
            foreach ($this->match as $k => $v) {
                if ($v[1] == -1) {
                    unset($this->match[$k]);
                }
            }
        }
        public function get(arraykey $pat = 0): string {
            return $this->match[$pat][0];
        }
        public function getOrNull(arraykey $pat = 0): ?string {
            $match = get_or_null($this->match, $pat);
            return $match === null ? new_null() : $match[0];
        }
        public function getOrEmpty(arraykey $pat = 0): string {
            $match = get_or_null($this->match, $pat);
            return $match === null ? '' : $match[0];
        }
        public function getOffset(arraykey $pat = 0): int {
            return $this->match[$pat][1];
        }
        public function getRange(arraykey $pat = 0): (int, int) {
            list($text, $offset) = $this->match[$pat];
            return tuple($offset, $offset + \strlen($text));
        }
        public function has(arraykey $pat): bool {
            return key_exists($this->match, $pat);
        }
        public function __toString(): string {
            return $this->get();
        }
        public function toArray(): array<arraykey, string> {
            return map_assoc($this->match, $x ==> $x[0]);
        }
    }
    final class PCREException extends \Exception {}
    function _pcre_compose(string $regex, string $options = ''): string {
        return '/'._EscapeCache::escape($regex).'/'.$options;
    }
    final class _EscapeCache {
        private static array<arraykey, string> $cache = [];
        public static function escape(string $regex): string {
            $escaped = get_or_null(self::$cache, $regex);
            if ($escaped !== null) {
                return $escaped;
            }
            // Dumb cache policy, but it works.
            if (size(self::$cache) >= 10000) {
                self::$cache = [];
            }
            return (self::$cache[$regex] = _pcre_escape($regex));
        }
    }
    function _pcre_escape(string $regex): string {
        // Insert a "\" before each unescaped "/".
        // I'm really hoping this simple state machine will get jitted to efficient
        // machine code.
        $result = '';
        $length = length($regex);
        $escape = false;
        for ($i = 0; $i < $length; $i++) {
            $char = $regex[$i];
            if ($escape) {
                $escape = false;
            } else if ($char === '/') {
                $result .= '\\';
            } else if ($char === '\\') {
                $escape = true;
            }
            $result .= $char;
        }
        return $result;
    }
    //----------------------------------------------------------------------------
    //  pcre utils
    //----------------------------------------------------------------------------
    function _pcre_get_error_message(int $error): string {
        switch ($error) {
        case \PREG_NO_ERROR:
            return 'No errors';
        case \PREG_INTERNAL_ERROR:
            return 'Internal PCRE error';
        case \PREG_BACKTRACK_LIMIT_ERROR:
            return 'Backtrack limit (pcre.backtrack_limit) was exhausted';
        case \PREG_RECURSION_LIMIT_ERROR:
            return 'Recursion limit (pcre.recursion_limit) was exhausted';
        case \PREG_BAD_UTF8_ERROR:
            return 'Malformed UTF-8 data';
        case \PREG_BAD_UTF8_OFFSET_ERROR:
            return
            'The offset didn\'t correspond to the beginning of a valid UTF-8 code point';
        case 6/* PREG_JIT_STACKLIMIT_ERROR */:
            return 'JIT stack space limit exceeded';
        default:
            return 'Unknown error';
        }
    }
    function _pcre_check_last_error(): void{
        $error = \preg_last_error();
        if ($error !== \PREG_NO_ERROR) {
            throw new PCREException(_pcre_get_error_message($error), $error);
        }
    }
}
// if colled directly in CLI, run the tests:
if( isset($argv[0]) && basename($argv[0]) == basename(__FILE__) ) {
    require_once __DIR__ . '/Test.php';
}