// Module for loading scripts asynchronously in a specific order.

var jsuLoader = (function() {
  
  // @public API
  const module = {};
  
  // @private
  const _concat = Array.prototype.concat;
  const _noop = function() {};
  
  let _scripts = [],
      _afterLoad = _noop,
      _onComplete = _noop;
      
  // @private
  let reduceRepeated = () =>
    _scripts
      // sorting strings is case sensitive.
      .sort((a, b) => a.toLowerCase() - b.toLowerCase())
      .reduce((uniques, item) => {
        // slice keeps reference when item is an object/array
        let last = uniques.slice(-1)[0];
        if (last !== item) uniques.push(item);
        return uniques;
      }, _scripts.slice(0, 1)); //initial value for @uniques

  // @private
  function createScriptTag() {
    // gets the first script in the list
    let script = _scripts.shift();
    console.warn("loading " + script );

    if (!script) {
      // all scripts were loaded
      return _onComplete();
    }
    let js = document.createElement('script');
    js.type = 'text/javascript';
    js.src = script;
    js.defer = true;
    js.onload = (event) => {
      console.warn("loaded " + script );
      _afterLoad(script);
      // loads the next script
      createScriptTag();
    };
    document.getElementsByTagName('body')[0].appendChild(js);
    //let s = document.getElementsByTagName('script')[0];
    //s.parentNode.insertBefore(js, s);
  }

  // @public
  function addScript(src) {
    if (src instanceof Array) {
      _scripts = _concat.apply(_scripts, src);
    }
    else {
      _scripts.push(src);
    }
    return module;
  }

  // @public
  function load() {
    // prevent duplicated scripts
    _scripts = reduceRepeated();
    createScriptTag();
  }

  // @public
  function afterLoad(fn) {
    if (fn instanceof Function) {
      _afterLoad = fn;
    }
    return module;
  }
  
  // @public
  function onComplete(fn) {
    if (fn instanceof Function) {
      _onComplete = fn;
    }
    return module;
  }

  // @public
  function reset() {
    _scripts.length = 0;
    _onComplete = _afterLoad = _noop;
    return module;
  }

  // @public API
  module.addScript = addScript;
  module.load = load;
  module.reset = reset;
  module.afterLoad = afterLoad;
  module.onComplete = onComplete;

  return module;
}());
