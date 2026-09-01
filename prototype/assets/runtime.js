/* Renderer for the design's template dialect.
 *
 *   {{ dotted.path }}                     text nodes + attribute values
 *   <sc-if value="{{ x }}">…</sc-if>      conditional
 *   <sc-for list="{{ xs }}" as="x">…</sc-for>
 *   onClick / onChange / onSubmit / onMouseEnter / onMouseLeave
 *   style-hover / style-focus / style-active
 *
 * Every render builds a detached tree, then patches the live DOM against it.
 * That keeps focus, caret position, scroll offsets and running CSS animations
 * intact — a re-`innerHTML` would destroy all four on every keystroke.
 */
(function (global) {
  'use strict';

  var SVG_NS = 'http://www.w3.org/2000/svg';
  var EVENTS = {
    onclick: 'click',
    onsubmit: 'submit',
    onmouseenter: 'mouseenter',
    onmouseleave: 'mouseleave'
  };

  /* ---- bindings -------------------------------------------------------- */

  // Verified against the source: every {{ … }} is a plain dotted path, so a
  // walk down the scope object is enough. No eval, no expression parser.
  function get(scope, path) {
    var parts = path.split('.');
    var v = scope[parts[0]];
    for (var i = 1; i < parts.length && v != null; i++) v = v[parts[i]];
    return v;
  }

  function interp(str, scope) {
    // A value that is *only* a binding keeps its real type — arrays for sc-for,
    // booleans for sc-if, functions for handlers.
    var whole = /^\{\{\s*([^}]+?)\s*\}\}$/.exec(str);
    if (whole) return get(scope, whole[1]);
    if (str.indexOf('{{') < 0) return str;
    return str.replace(/\{\{\s*([^}]+?)\s*\}\}/g, function (_, p) {
      var v = get(scope, p);
      return v == null ? '' : v;
    });
  }

  /* ---- style-hover / -focus / -active ---------------------------------- */

  // Generated CSS rules rather than JS listeners: nothing to restore on
  // mouseleave, and :hover survives a re-render. Inline style outranks a class
  // rule, hence !important on each declaration.
  var sheet = null, pseudoCache = Object.create(null), pseudoN = 0;

  function pseudoClass(kind, css) {
    var key = kind + '|' + css;
    if (pseudoCache[key]) return pseudoCache[key];
    if (!sheet) {
      var tag = document.createElement('style');
      document.head.appendChild(tag);
      sheet = tag.sheet;
    }
    var cls = 'dc-' + kind + '-' + pseudoN++;
    var body = css.split(';').filter(Boolean).map(function (d) {
      return d.trim() + ' !important';
    }).join(';');
    sheet.insertRule('.' + cls + ':' + kind + '{' + body + '}', sheet.cssRules.length);
    pseudoCache[key] = cls;
    return cls;
  }

  /* ---- render ---------------------------------------------------------- */

  function renderChildren(node, scope, out, svg) {
    for (var n = node.firstChild; n; n = n.nextSibling) renderNode(n, scope, out, svg);
  }

  function renderNode(node, scope, out, svg) {
    if (node.nodeType === 3) {
      var t = node.nodeValue;
      if (t.indexOf('{{') < 0) {
        out.appendChild(document.createTextNode(t));
      } else {
        var v = interp(t, scope);
        out.appendChild(document.createTextNode(v == null ? '' : String(v)));
      }
      return;
    }
    if (node.nodeType !== 1) return;

    var tag = node.tagName.toLowerCase();

    if (tag === 'sc-if') {
      if (interp(node.getAttribute('value'), scope)) renderChildren(node, scope, out, svg);
      return;
    }

    if (tag === 'sc-for') {
      var list = interp(node.getAttribute('list'), scope);
      var as = node.getAttribute('as');
      if (!list || !list.length) return;
      for (var i = 0; i < list.length; i++) {
        // Prototype chain = scope nesting for free: the loop variable shadows,
        // everything outside stays reachable, and nothing gets copied.
        var inner = Object.create(scope);
        inner[as] = list[i];
        renderChildren(node, inner, out, svg);
      }
      return;
    }

    svg = svg || tag === 'svg';
    var el = svg ? document.createElementNS(SVG_NS, tag) : document.createElement(tag);
    var classes = [];
    var handlers = null;
    var attrs = node.attributes;

    for (var a = 0; a < attrs.length; a++) {
      // XML keeps attribute case, so match lowercased but write back the
      // original — SVG's viewBox et al. are case-sensitive.
      var rawName = attrs[a].name, name = rawName.toLowerCase(), raw = attrs[a].value;

      // canvas-only annotations
      if (name.lastIndexOf('hint-placeholder-', 0) === 0 || name === 'data-screen-label') continue;

      if (name === 'style-hover' || name === 'style-focus' || name === 'style-active') {
        classes.push(pseudoClass(name.slice(6), raw));
        continue;
      }
      if (name === 'class') { classes.push(raw); continue; }

      if (EVENTS[name]) {
        (handlers || (handlers = {}))[EVENTS[name]] = interp(raw, scope);
        continue;
      }
      if (name === 'onchange') {
        // A <select> fires `change`; text fields need `input` so the design's
        // live-filtering search behaves the way it does on the canvas.
        (handlers || (handlers = {}))[tag === 'select' ? 'change' : 'input'] = interp(raw, scope);
        continue;
      }

      var v = interp(raw, scope);

      // Form values go on the property, never the attribute — see syncAttrs.
      if (name === 'value' && (tag === 'input' || tag === 'textarea' || tag === 'select')) {
        el.__value = v == null ? '' : String(v);
        continue;
      }
      if (v == null || v === false) continue;
      el.setAttribute(rawName, v === true ? '' : v);
    }

    if (classes.length) el.setAttribute('class', classes.join(' '));
    if (handlers) el.__on = handlers;

    renderChildren(node, scope, el, svg);
    out.appendChild(el);
  }

  /* ---- patch ----------------------------------------------------------- */

  function dispatch(e) {
    var h = this.__on && this.__on[e.type];
    if (h) h(e);
  }

  // A subtree inserted whole never passes through syncAttrs, so its handlers
  // and form values have to be wired on the way in.
  function hydrate(node) {
    if (node.nodeType !== 1) return;
    if (node.__on) for (var k in node.__on) node.addEventListener(k, dispatch);
    if (node.__value !== undefined && node.value !== node.__value) node.value = node.__value;
    for (var c = node.firstChild; c; c = c.nextSibling) hydrate(c);
  }

  function syncAttrs(live, next) {
    var i, a;
    for (i = next.attributes.length - 1; i >= 0; i--) {
      a = next.attributes[i];
      if (live.getAttribute(a.name) !== a.value) live.setAttribute(a.name, a.value);
    }
    for (i = live.attributes.length - 1; i >= 0; i--) {
      a = live.attributes[i];
      if (!next.hasAttribute(a.name)) live.removeAttribute(a.name);
    }

    // One listener per event type, resolving the current handler at call time —
    // so re-rendering never stacks duplicates.
    var on = next.__on;
    if (on) {
      for (var k in on) {
        if (!live.__on || !(k in live.__on)) live.addEventListener(k, dispatch);
      }
    }
    live.__on = on || null;

    // Write the value only when it genuinely differs. While you type, state
    // already equals the field's content, so no write happens and the caret
    // stays put.
    if (next.__value !== undefined && live.value !== next.__value) live.value = next.__value;
  }

  function patch(live, next) {
    if (live.nodeType === 1 && next.nodeType === 1) syncAttrs(live, next);

    // Snapshot: appending a node to `live` removes it from `next`.
    var incoming = [].slice.call(next.childNodes);
    for (var i = 0; i < incoming.length; i++) {
      var n = incoming[i], l = live.childNodes[i];
      if (!l) { live.appendChild(n); hydrate(n); continue; }
      if (l.nodeType !== n.nodeType || (l.nodeType === 1 && l.tagName !== n.tagName)) {
        live.replaceChild(n, l);
        hydrate(n);
        continue;
      }
      if (l.nodeType === 3) {
        if (l.nodeValue !== n.nodeValue) l.nodeValue = n.nodeValue;
        continue;
      }
      patch(l, n);
    }
    while (live.childNodes.length > incoming.length) live.removeChild(live.lastChild);
  }

  /* ---- base class + mount ---------------------------------------------- */

  function DCLogic(props) {
    this.props = props || {};
    this.state = {};
  }

  DCLogic.prototype.setState = function (patchOrFn) {
    var next = typeof patchOrFn === 'function' ? patchOrFn(this.state) : patchOrFn;
    Object.assign(this.state, next);
    if (!this.__draw || this.__pending) return;
    // Batch: a handler firing three setStates still paints once.
    this.__pending = true;
    var self = this;
    Promise.resolve().then(function () {
      self.__pending = false;
      self.__draw();
    });
  };

  // The template must be parsed as XML, not HTML. Inside a <table>, the HTML
  // parser foster-parents any non-table element — every <sc-for> wrapping a
  // <tr> would be hoisted out of the table and silently emptied, leaving the
  // rows behind with no loop variable. XML has no such rule, so the authored
  // structure survives intact.
  function parseTemplate(text) {
    var doc = new DOMParser().parseFromString('<dc-root>' + text + '</dc-root>', 'application/xml');
    var err = doc.querySelector('parsererror');
    if (err) throw new Error('template is not well-formed XML: ' + err.textContent.trim());
    return doc.documentElement;
  }

  function mount(source, host, component) {
    var template = typeof source === 'string' ? parseTemplate(source)
                 : source.tagName === 'SCRIPT' ? parseTemplate(source.textContent)
                 : source.content;
    component.__draw = function () {
      var frag = document.createDocumentFragment();
      renderChildren(template, component.renderVals(), frag, false);
      patch(host, frag);
    };
    component.__draw();
    if (component.componentDidMount) component.componentDidMount();
    return component;
  }

  global.DCLogic = DCLogic;
  global.DCRuntime = {
    mount: mount, render: renderChildren, patch: patch, parseTemplate: parseTemplate,
    interp: interp, get: get, pseudoClass: pseudoClass
  };
})(window);
