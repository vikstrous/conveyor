/**
 * The abstract logic templating engine.
 * @package Conveyor
 * @author Viktor Stanchev (me@viktorstanchev.com)
 */

function preg_quote(str, delimiter) {
    // Quote regular expression characters plus an optional character
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/preg_quote
    // +   original by: booeyOH
    // +   improved by: Ates Goral (http://magnetiq.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: preg_quote("$40");
    // *     returns 1: '\$40'
    // *     example 2: preg_quote("*RRRING* Hello?");
    // *     returns 2: '\*RRRING\* Hello\?'
    // *     example 3: preg_quote("\\.+*?[^]$(){}=!<>|:");
    // *     returns 3: '\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:'
    return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}

var Conveyor = {

    _pattern_to_regex: function(pattern) {
        var pieces = pattern.split(new RegExp("(?!=\\\\)\\/"));
        var result = '';
        for (var key in pieces) {
            var value = pieces[key];
            switch (value) {
            case '**':
                result += '\\/.+';
                break;
            case '*':
                result += '\\/[^\/]+';
                break;
            default:
                result += '\\/' + preg_quote(value);
            }
        }
        if (pattern.length > 0 && pattern[0] != '/') result = result.substring(2) + '$';
        else result = '^' + result.substring(2) + '$';
        return new RegExp(result, 'i');
    },

    _manipulate: function(logic, data, path) {
        var fn, pattern;
        var should_discard = false;
        var del_fn = function() {
                should_discard = true;
            };
        if (path === '') {
            for (pattern in logic) {
                fn = logic[pattern];
                if ('/'.match(Conveyor._pattern_to_regex(pattern))) {
                    should_discard = false;
                    data = fn(data, '/', del_fn);
                    if (should_discard) {
                        data = undefined;
                        break;
                    }
                }
            }
        }
        if (data && (typeof data === 'object' || typeof data === 'array')) {
            var new_data = data.length ? [] : {};
            for (var key in data) {
                var value = data[key];
                var next_path = path + '/' + key.replace(new RegExp('/', 'g'), '\/');
                var matched = false;
                for (pattern in logic) {
                    fn = logic[pattern];
                    if (next_path.match(Conveyor._pattern_to_regex(pattern))) {
                        //matched!
                        matched = true;
                        should_discard = false;
                        var res = fn(value, next_path, del_fn);
                        if (should_discard) {
                            break;
                        } else {
                            if (data.length) new_data.push(res);
                            else new_data[key] = res;
                        }
                    }
                }
                if (!matched) {
                    if (data.length) new_data.push(value);
                    else new_data[key] = value;
                }
                if (Number(key) == key) {
                    key = Number(key);
                }
            }
            for (key in new_data) {
                var val = new_data[key];
                var result;
                var next_p = path + '/' + key.replace(new RegExp('/', 'g'), '\/');
                if (typeof new_data === 'array') {
                    result = Conveyor._manipulate(logic, new_data[key], next_p);
                    if (result !== null) {
                        new_data.push(result);
                    }
                }
                if (typeof new_data === 'object') {
                    result = Conveyor._manipulate(logic, new_data[key], next_p);
                    if (result !== null) {
                        new_data[key] = result;
                    }
                }
            }
            data = new_data;
        }
        if (data) return data;
        else return null;
    },

    make_namer: function(name) {
        //return a function that will name
        return function(data, path) {
            return Conveyor.name(data, name);
        };
    },

    name: function(obj, name) {
        var ret = {};
        ret[name] = obj;
        return ret;
    },

    make_rowifier: function(columns, name) {
        //return a function that will rowify
        return function(data, path) {
            return Conveyor.rowify(data, columns, name);
        };
    },

    rowify: function(data, columns, name) {
        var new_data = [];
        var dataCount = data.length;
        for (i = 0; i < dataCount; i += columns) {
            var accumulate = [];
            for (j = 0; j < columns && i + j < dataCount; j++) {
                accumulate.push(data[i + j]);
            }
            if (name && name !== '') {
                var obj = {};
                obj[name] = accumulate;
                new_data.push(obj);
            } else {
                new_data.push(accumulate);
            }
        }
        return new_data;
    },


    apply: function(data, logic) {
        if (typeof logic === 'object') {
            return Conveyor._manipulate(logic, data, '');
        } else {
            return Conveyor._manipulate({
                '/': logic
            }, data, '');
        }
    },

    render: function(template, data, logic) {
        if (logic) data = Conveyor.apply(data, logic);

        return Mustache.render(template, data);
    }
};
