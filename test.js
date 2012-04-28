var ConveyorTest = {

	test_all: function() {
		ConveyorTest.test_pattern_to_regex();
		ConveyorTest.test_apply();
	},
	test_pattern_to_regex: function() {
		console.log('test_pattern_to_regex()');
		var match;
		var pass = {
			'/': '/',
			'/child1': '/child1',
			'/child?': '/child?',
			'/*': '/anychild',
			'/*/one': '/anything/one',
			'one': '/anything/one',
			'anything/one': '/anything/one',
			'*/one': '/anything/one',
			'*/*': '/anything/one',
			'**': '/anything/one',
			'*': '/anything/one',
			'th\\ing\\/': '/anything/th\\ing\\/',
			'thing\\/': '/anything/thing\\/',
			'anything/**': '/anything/one',
			'anything2/**': '/anything2/one/two'
		};
		var fail = {
			'': '/',
			'/': '/notroot',
			'test': '/test/not',
			'anything/**': '/not/one'
		};
		for (var pattern in pass) {
			match = pass[pattern];
			regex = Conveyor._pattern_to_regex(pattern);
			if (!match.match(new RegExp(regex))) console.log('Failed! ' + match + ' ' + regex + ' ' + pattern);
			else console.log('Pass!');
		}
		for (pattern in fail) {
			match = fail[pattern];
			regex = Conveyor._pattern_to_regex(pattern);
			if (match.match(new RegExp(regex))) console.log('Failed! ' + match + ' ' + regex + ' ' + pattern);
			else console.log('Pass!');
		}
	},
	test_apply: function() {
		console.log('test_apply()');
		tests = [{
			'data': {
				'hello': 'world'
			},
			'logic': {
				'/hello': function(value, path) {
					return 'Conveyor';
				}
			},
			'result': {
				'hello': 'Conveyor'
			}
		}, {
			'data': ['world1', 'world2', 'world3'],
			'logic': {
				'/*': function(value, path) {
					return value + ' visited';
				}
			},
			'result': ['world1 visited', 'world2 visited', 'world3 visited']
		}, {
			'data': {
				'hello': ['world1', 'world2', 'world3']
			},
			'logic': {
				'/hello/*': function(value, path) {
					return value + ' visited';
				}
			},
			'result': {
				'hello': ['world1 visited', 'world2 visited', 'world3 visited']
			}
		}, {
			'data': {
				'hello': ['world0', 'world1', 'world2']
			},
			'logic': {
				'/hello/*': function(value, path) {
					if (path == '/hello/1') {
						return undefined;
					} else {
						return value + ' visited';
					}
				}
			},
			'result': {
				'hello': ['world0 visited', undefined, 'world2 visited']
			}
		}, {
			'data': {
				'hello': ['world0', 'world1', 'world2']
			},
			'logic': {
				'/hello/*': function(value, path) {
					return value + ' visited';
				},
				'/hello/1': function(value, path) {
					return undefined;
				}
			},
			'result': {
				'hello': ['world0 visited', undefined, 'world2 visited']
			}
		}, {
			'data': {
				'hello': {
					'a': 'world0',
					'b': 'world1',
					'c': 'world2'
				}
			},
			'logic': {
				'/hello/*': function(value, path) {
					return value + ' visited';
				},
				'/hello/b': function(value, path) {
					return undefined;
				}
			},
			'result': {
				'hello': {
					'a': 'world0 visited',
					'c': 'world2 visited'
				}
			}
		}, {
			'data': {
				'hello': 'world'
			},
			'logic': {
				'/': function(value, path) {
					return undefined;
				}
			},
			'result': undefined
		}, {
			'data': {
				'hello': {
					'test': 'world'
				}
			},
			'logic': {
				'test': function(value, path) {
					return 'Conveyor';
				}
			},
			'result': {
				'hello': {
					'test': 'Conveyor'
				}
			}
		}, {
			'data': 'data',
			'logic': {
				'/': Conveyor.make_namer('weee')
			},
			'result': {
				'weee': 'data'
			}
		}, {
			'data': [0, 1, 2, 3],
			'logic': {
				'/': Conveyor.make_rowifier(2, 'row')
			},
			'result': [{
				'row': [0, 1]
			}, {
				'row': [2, 3]
			}]
		}, {
			'data': [0, 1, 2, 3],
			'logic': {
				'/': Conveyor.make_rowifier(2)
			},
			'result': [
				[0, 1],
				[2, 3]
			]
		}];
		for (var key in tests) {
			var test = tests[key];
			res = Conveyor.apply(test.data, test.logic);
			if (JSON.stringify(res) == JSON.stringify(test.result)) {
				console.log("Pass!");
			} else {
				console.log("Test " + key + " failed.");
				console.log(res);
				console.log("should be");
				console.log(test.result);
			}
		}
	}
};
ConveyorTest.test_all();
