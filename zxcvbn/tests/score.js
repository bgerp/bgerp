/* Synthetic password examples only. No account or password changes. */
(function (root) {
    'use strict';

    /** Runs identical scoring checks in Node and a browser. */
    function run(zxcvbn) {
        var checks = 0;
        function check(ok, label) {
            if (!ok) throw new Error(label);
            checks++;
        }
        ['', '123456', 'password', 'qwerty', 'p@ssw0rd', 'aaaaaaaaaaaaaaaaaaaa'].forEach(function (text) {
            var result = zxcvbn(text);
            check(result.score === 0, 'Common pattern must remain weak');
            check(result.password === text, 'Exact input retained');
        });
        ['correct horse battery staple', 'L7!qB2#vR9@xK4%t'].forEach(function (text) {
            check(zxcvbn(text).score === 4, 'Long synthetic example');
        });
        ['Кирилица-случайна-72!', '🙂', '00123', 'quoted\'"\\value'].forEach(function (text) {
            var result = zxcvbn(text);
            check(result.password === text, 'Unicode and leading zeroes');
            check(result.score >= 0 && result.score <= 4 && isFinite(result.guesses), 'Finite estimate');
        });
        check(zxcvbn('TestExampleAccount', ['TestExampleAccount']).score === 0, 'User dictionary');
        check(zxcvbn('123456').feedback.suggestions.length > 0, 'Feedback remains available');
        check(zxcvbn('x'.repeat(128)).score === 1, 'Long repeated input');
        return {checks: checks};
    }

    if (typeof module === 'object' && module.exports) {
        module.exports = run;
        if (require.main === module) console.log(JSON.stringify(run(require('../dropboxLib/zxcvbn.js'))));
    } else {
        root.testZxcvbnScore = run;
    }
}(typeof window === 'object' ? window : this));
