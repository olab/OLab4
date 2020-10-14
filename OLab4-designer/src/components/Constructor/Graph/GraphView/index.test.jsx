// @flow
import * as d3 from 'd3';
import React from 'react';
import { render } from 'react-dom';

import { shallow } from 'enzyme';

import GraphUtils from '../utilities/graph-utils';
import { GraphView } from '.';

jest.mock('react-dom', () => ({
  render: jest.fn(),
}));

describe('GraphView component', () => {
  let output = {};
  let nodes;
  let edges;
  let edgeTypes;
  let selected;
  let onDeleteNode;
  let onSelectNode;
  let onCreateNode;
  let onCreateEdge;
  let onDeleteEdge;
  let onUpdateNode;
  let onSwapEdge;
  let onSelectEdge;
  let onUndo;
  let onRedo;
  let onCopySelected;
  let onPasteSelected;
  let layoutEngineType;
  let zoomControlsRef;
  beforeEach(() => {
    nodes = [];
    edges = [];
    edgeTypes = {};
    selected = null;
    layoutEngineType = 'None';
    onDeleteNode = jest.fn();
    onSelectNode = jest.fn();
    onCreateNode = jest.fn();
    onCreateEdge = jest.fn();
    onDeleteEdge = jest.fn();
    onUpdateNode = jest.fn();
    onSwapEdge = jest.fn();
    onSelectEdge = jest.fn();
    onUndo = jest.fn();
    onRedo = jest.fn();
    onCopySelected = jest.fn();
    onPasteSelected = jest.fn();

    zoomControlsRef = {
      current: document.createElement('div'),
    };

    jest.spyOn(document, 'querySelector').mockReturnValue({
      getBoundingClientRect: jest.fn().mockReturnValue({
        width: 0,
        height: 0,
      }),
    });

    // this gets around d3 being readonly, we need to customize the event object
    let globalEvent = {
      sourceEvent: {},
    };
    // $FlowFixMe
    Object.defineProperty(d3, 'event', {
      get: () => globalEvent,
      set: (event) => {
        globalEvent = event;
      },
    });
    let globalMouse = {};
    // $FlowFixMe
    Object.defineProperty(d3, 'mouse', {
      get: () => globalMouse,
      set: (mouse) => {
        globalMouse = mouse;
      },
    });

    output = shallow(
      <GraphView
        ref={React.createRef()}
        nodes={nodes}
        edges={edges}
        selected={selected}
        edgeTypes={edgeTypes}
        onSelectNode={onSelectNode}
        onCreateNode={onCreateNode}
        onUpdateNode={onUpdateNode}
        onDeleteNode={onDeleteNode}
        onSelectEdge={onSelectEdge}
        onCreateEdge={onCreateEdge}
        onSwapEdge={onSwapEdge}
        onDeleteEdge={onDeleteEdge}
        onCopySelected={onCopySelected}
        onPasteSelected={onPasteSelected}
        onUndo={onUndo}
        onRedo={onRedo}
        layoutEngineType={layoutEngineType}
        zoomControlsRef={zoomControlsRef}
        ACTION_SAVE_MAP_TO_UNDO={jest.fn()}
      />,
    );
  });

  describe('render method', () => {
    it('renders', () => {
      expect(output.getElement()).not.toBeNull();
    });
  });

  describe('renderGraphControls method', () => {
    beforeEach(() => {
      output.instance().viewWrapper = {
        current: document.createElement('div'),
      };
      output.instance().viewWrapper.current.width = 500;
      output.instance().viewWrapper.current.height = 500;
    });

    it('uses ReactDOM.render to async render the GraphControls', () => {
      output.setProps({
        zoomControlsRef,
      });
      output.setState({
        viewTransform: {
          k: 0.6,
        },
      });
      output.instance().renderGraphControls();
      expect(render).toHaveBeenCalled();
    });
  });

  describe('renderEdges method', () => {
    beforeEach(() => {
      jest.spyOn(output.instance(), 'asyncRenderEdge');
    });

    it('does nothing when there are no entities', () => {
      output.instance().entities = null;
      output.instance().renderEdges();
      expect(output.instance().asyncRenderEdge).not.toHaveBeenCalled();
    });

    it('does nothing while dragging an edge', () => {
      output.setState({
        draggingEdge: true,
      });
      output.instance().entities = [];
      output.instance().renderEdges();
      expect(output.instance().asyncRenderEdge).not.toHaveBeenCalled();
    });

    it('calls asyncRenderEdge for each edge', () => {
      output.setProps({
        edges: [
          {
            source: 'b',
            target: 'a',
          },
          {
            source: 'c',
            target: 'a',
          },
        ],
      });
      // modifying the edges will call renderEdges, we need to reset this count.
      output.instance().entities = [];
      output.instance().renderEdges();
      expect(output.instance().asyncRenderEdge).toHaveBeenCalledTimes(2);
    });
  });

  describe('syncRenderEdge method', () => {
    beforeEach(() => {
      jest.spyOn(output.instance(), 'renderEdge');
      jest.spyOn(output.instance(), 'getEdgeComponent').mockReturnValue('blah');
    });

    it('sets up a renderEdge call synchronously', () => {
      const expectedEdge = {
        source: 'a',
        target: 'b',
      };
      output.instance().syncRenderEdge(expectedEdge);
      expect(output.instance().renderEdge).toHaveBeenCalledWith('edge-a-b', 'blah', expectedEdge, false);
    });

    it('uses a custom idVar', () => {
      const expectedEdge = {
        source: 'a',
      };
      output.instance().syncRenderEdge(expectedEdge);
      expect(output.instance().renderEdge).toHaveBeenCalledWith('edge-custom', 'blah', expectedEdge, false);
    });
  });

  describe('asyncRenderEdge method', () => {
    beforeEach(() => {
      jest.spyOn(window, 'requestAnimationFrame').mockImplementation((cb) => {
        cb();
        return true;
      });
    });
    afterEach(() => {
      window.requestAnimationFrame.mockRestore();
    });

    it('renders asynchronously', () => {
      jest.spyOn(output.instance(), 'syncRenderEdge');
      const edge = {
        source: 'a',
        target: 'b',
      };
      output.instance().asyncRenderEdge(edge);

      expect(output.instance().edgeTimeouts['edges-a-b']).toBeDefined();
      expect(requestAnimationFrame).toHaveBeenCalledTimes(1);
      expect(output.instance().syncRenderEdge).toHaveBeenCalledWith(edge, false);
    });
  });

  describe('renderEdge method', () => {
    beforeEach(() => {
      output.instance().entities = {
        appendChild: jest.fn(),
      };
    });

    it('appends an edge element into the entities element', () => {
      const element = document.createElement('g');
      const edge = {
        source: 'a',
        target: 'b',
      };
      output.instance().renderEdge('test', element, edge);

      expect(output.instance().entities.appendChild).toHaveBeenCalled();
    });

    it('replaces an edge in an existing container', () => {
      const element = document.createElement('g');
      const container = document.createElement('g');
      container.id = 'test-container';
      jest.spyOn(document, 'getElementById').mockReturnValue(container);
      const edge = {
        source: 'a',
        target: 'b',
      };
      output.instance().renderEdge('test', element, edge);

      expect(output.instance().entities.appendChild).not.toHaveBeenCalled();
      expect(render).toHaveBeenCalledWith(element, container);
    });

    afterEach(() => {
      jest.resetAllMocks();
    });
  });

  describe('getEdgeComponent method', () => {
    beforeEach(() => {
      nodes = [
        { id: 'a' },
        { id: 'b' },
      ];
    });

    it('returns an Edge component', () => {
      const edge = {
        source: 'a',
        target: 'b',
      };
      output.setProps({
        nodes,
      });

      const result = output.instance().getEdgeComponent(edge);
      expect(result.type.prototype.constructor.name).toEqual('Edge');
      expect(result.props.data).toEqual(edge);
      expect(result.props.sourceNode).toEqual(nodes[0]);
      expect(result.props.targetNode).toEqual(nodes[1]);
    });

    it('handles missing nodes', () => {
      const edge = {
        source: 'a',
        target: 'b',
      };
      const result = output.instance().getEdgeComponent(edge);
      expect(result.type.prototype.constructor.name).toEqual('Edge');
      expect(result.props.data).toEqual(edge);
      expect(result.props.sourceNode).toEqual(null);
      expect(result.props.targetNode).toEqual(undefined);
    });

    it('handles a targetPosition', () => {
      const edge = {
        source: 'a',
        targetPosition: { x: 0, y: 10 },
      };
      output.setProps({
        nodes,
      });
      const result = output.instance().getEdgeComponent(edge);
      expect(result.type.prototype.constructor.name).toEqual('Edge');
      expect(result.props.data).toEqual(edge);
      expect(result.props.sourceNode).toEqual(nodes[0]);
      expect(result.props.targetNode).toEqual({ x: 0, y: 10 });
    });
  });

  describe('renderNodes method', () => {
    beforeEach(() => {
      jest.spyOn(output.instance(), 'asyncRenderNode');
      nodes = [
        { id: 'a' },
        { id: 'b' },
      ];
      output.setProps({
        nodes,
      });
    });

    it('returns early when there are no entities', () => {
      // asyncRenderNode gets called when new nodes are added. Reset the calls.

      output.instance().renderNodes();
      expect(output.instance().asyncRenderNode).not.toHaveBeenCalled();
    });

    it('calls asynchronously renders each node', () => {
      output.instance().entities = [];
      output.instance().renderNodes();
      expect(output.instance().asyncRenderNode).toHaveBeenCalledTimes(2);
    });
  });

  describe('isEdgeSelected method', () => {
    let edge;
    beforeEach(() => {
      edge = {
        source: 'a',
        target: 'b',
      };
      edges.push(edge);
    });

    it('returns true when the edge is selected', () => {
      selected = edge;
      output.setProps({
        edges,
        selected,
      });

      const result = output.instance().isEdgeSelected(edge);
      expect(result).toEqual(true);
    });

    it('returns false when the edge is not selected', () => {
      selected = {
        source: 'b',
        target: 'c',
      };
      output.setProps({
        edges,
        selected,
      });

      const result = output.instance().isEdgeSelected(edge);
      expect(result).toEqual(false);
    });
  });

  describe('syncRenderNode method', () => {
    it('renders a node and connected edges', () => {
      const node = { id: 'a' };
      const nodesProp = [node];
      output.setProps({
        nodes: nodesProp,
      });
      jest.spyOn(output.instance(), 'renderNode');
      jest.spyOn(output.instance(), 'renderConnectedEdgesFromNode');

      output.instance().syncRenderNode(node, 0);

      expect(output.instance().renderNode).toHaveBeenCalledWith('node-a', expect.any(Object));
      expect(output.instance().renderConnectedEdgesFromNode).toHaveBeenCalled();
    });
  });

  describe('asyncRenderNode method', () => {
    beforeEach(() => {
      jest.spyOn(window, 'requestAnimationFrame').mockImplementation((cb) => {
        cb();
        return true;
      });
    });
    afterEach(() => {
      window.requestAnimationFrame.mockRestore();
    });

    it('renders asynchronously', () => {
      jest.spyOn(output.instance(), 'syncRenderNode');
      const node = { id: 'a' };
      output.instance().asyncRenderNode(node);

      expect(output.instance().nodeTimeouts['nodes-a']).toBeDefined();
      expect(requestAnimationFrame).toHaveBeenCalledTimes(1);
      expect(output.instance().syncRenderNode).toHaveBeenCalledWith(node);
    });
  });

  describe('renderConnectedEdgesFromNode method', () => {
    let node;
    beforeEach(() => {
      jest.spyOn(output.instance(), 'asyncRenderEdge');
      node = {
        id: 'a',
        incomingEdges: [
          { source: 'b', target: 'a' },
        ],
        outgoingEdges: [
          { source: 'a', target: 'c' },
        ],
      };
    });

    it('does nothing while dragging an edge', () => {
      output.setState({
        draggingEdge: true,
      });

      output.instance().renderConnectedEdgesFromNode(node);

      expect(output.instance().asyncRenderEdge).not.toHaveBeenCalled();
    });

    it('renders edges for incoming and outgoing edges', () => {
      output.instance().renderConnectedEdgesFromNode(node);

      expect(output.instance().asyncRenderEdge).toHaveBeenCalledTimes(2);
    });
  });

  describe('renderNode method', () => {
    beforeEach(() => {
      output.instance().entities = {
        appendChild: jest.fn(),
      };
    });

    it('appends a node element into the entities element', () => {
      const element = document.createElement('g');
      output.instance().renderNode('test', element);

      expect(output.instance().entities.appendChild).toHaveBeenCalled();
    });

    it('replaces a node in an existing container', () => {
      const element = document.createElement('g');
      const container = document.createElement('g');
      container.id = 'test-container';
      jest.spyOn(document, 'getElementById').mockReturnValue(container);
      output.instance().renderNode('test', element);

      expect(output.instance().entities.appendChild).not.toHaveBeenCalled();
      expect(render).toHaveBeenCalledWith(element, container);
    });
  });

  describe('getNodeComponent method', () => {
    let node;
    beforeEach(() => {
      node = { id: 'a' };
    });

    it('returns a Node', () => {
      const result = output.instance().getNodeComponent('test', node, 0);

      expect(result.type.prototype.constructor.name).toEqual('Node');
      expect(result.props.id).toEqual('test');
      expect(result.props.data).toEqual(node);
      expect(result.props.isSelected).toEqual(false);
    });

    it('returns a selected node', () => {
      output.setProps({
        nodes: [node],
        selected: node,
      });
      const result = output.instance().getNodeComponent('test', node, 0);
      expect(result.props.isSelected).toEqual(true);
    });
  });

  describe('renderBackground method', () => {
    it('uses the renderBackground callback', () => {
      const renderBackground = jest.fn().mockReturnValue('test');
      output.setProps({
        gridSize: 1000,
        renderBackground,
      });

      const result = output.instance().renderBackground();

      expect(renderBackground).toHaveBeenCalledWith(1000);
      expect(result).toEqual('test');
    });

    it('renders the background', () => {
      const result = output.instance().renderBackground();
      expect(result.type.prototype.constructor.name).toEqual('Background');
      expect(result.props.gridSize).toEqual(40960);
      expect(result.props.backgroundFillId).toEqual('#grid');
    });
  });

  describe('renderView method', () => {
    it('sets up the view and calls renderNodes asynchronously', () => {
      jest.useFakeTimers();
      jest.clearAllTimers();
      jest.spyOn(output.instance(), 'renderNodes');
      output.setState({
        viewTransform: 'test',
      });
      output.instance().selectedView = d3.select(document.createElement('g'));

      output.instance().renderView();

      jest.runAllTimers();

      expect(output.instance().renderNodes).toHaveBeenCalled();
      expect(output.instance().selectedView.attr('transform')).toEqual('test');
    });
  });

  describe('modifyZoom', () => {
    beforeEach(() => {
      jest.spyOn(output.instance(), 'setZoom');
      output.instance().viewWrapper = {
        current: document.createElement('div'),
      };
      output.instance().viewWrapper.current.width = 500;
      output.instance().viewWrapper.current.height = 500;
      output.instance().setState({
        viewTransform: {
          k: 0.4,
          x: 50,
          y: 50,
        },
      });
    });

    it('modifies the zoom', () => {
      output.instance().modifyZoom(0.1, 5, 10, 100);
      expect(output.instance().setZoom).toHaveBeenCalledWith(0.44000000000000006, 55, 60, 100);
    });

    it('does nothing when targetZoom is too small', () => {
      output.instance().modifyZoom(-100, 5, 10, 100);
      expect(output.instance().setZoom).not.toHaveBeenCalled();
    });

    it('does nothing when targetZoom is too large', () => {
      output.instance().modifyZoom(100, 5, 10, 100);
      expect(output.instance().setZoom).not.toHaveBeenCalled();
    });

    it('uses defaults', () => {
      output.instance().modifyZoom();
      expect(output.instance().setZoom).toHaveBeenCalledWith(0.4, 50, 50, 0);
    });
  });

  describe('handleZoomToFit method', () => {
    beforeEach(() => {
      jest.spyOn(output.instance(), 'setZoom');
      output.instance().viewWrapper = {
        current: document.createElement('div'),
      };
      // this gets around output.instance().viewWrapper.client[Var] being readonly,
      // we need to customize the object
      let globalWidth = 0;
      // $FlowFixMe
      Object.defineProperty(output.instance().viewWrapper.current, 'clientWidth', {
        get: () => globalWidth,
        set: (clientWidth) => {
          globalWidth = clientWidth;
        },
      });
      let globalHeight = 0;
      // $FlowFixMe
      Object.defineProperty(output.instance().viewWrapper.current, 'clientHeight', {
        get: () => globalHeight,
        set: (clientHeight) => {
          globalHeight = clientHeight;
        },
      });
      output.instance().viewWrapper.current.clientWidth = 500;
      output.instance().viewWrapper.current.clientHeight = 500;
      output.instance().setState({
        viewTransform: {
          k: 0.4,
          x: 50,
          y: 50,
        },
      });
      output.instance().entities = document.createElement('g');
      output.instance().entities.getBBox = jest.fn().mockReturnValue({
        width: 400,
        height: 300,
        x: 5,
        y: 10,
      });
    });

    it('modifies the zoom to fit the elements', () => {
      output.instance().handleZoomToFit();
      expect(output.instance().entities.getBBox).toHaveBeenCalled();
      expect(output.instance().setZoom).toHaveBeenCalledWith(1.125, 19.375, 70, 750);
    });

    it('uses defaults for minZoom and maxZoom', () => {
      output.setProps({
        maxZoom: null,
        minZoom: null,
        zoomDur: 100,
      });
      output.instance().handleZoomToFit();
      expect(output.instance().setZoom).toHaveBeenCalledWith(null, 250, 250, 100);
    });

    it('does not modify the zoom', () => {
      output.instance().entities.getBBox.mockReturnValue({
        width: 0,
        height: 0,
        x: 5,
        y: 5,
      });
      output.instance().handleZoomToFit();
      expect(output.instance().setZoom).toHaveBeenCalledWith(0.825, 0, 0, 750);
    });

    it('uses the maxZoom when k is greater than max', () => {
      output.instance().entities.getBBox.mockReturnValue({
        width: 5,
        height: 5,
        x: 5,
        y: 5,
      });
      output.instance().handleZoomToFit();
      expect(output.instance().setZoom).toHaveBeenCalledWith(1.5, 238.75, 238.75, 750);
    });

    it('uses the minZoom when k is less than min', () => {
      output.instance().entities.getBBox.mockReturnValue({
        width: 10000,
        height: 10000,
        x: 5,
        y: 5,
      });
      output.instance().handleZoomToFit();
      expect(output.instance().setZoom).toHaveBeenCalledWith(0.15, -500.75, -500.75, 750);
    });
  });

  describe('handleZoomEnd method', () => {
    beforeEach(() => {
      jest.spyOn(GraphUtils, 'removeElementFromDom');
      jest.spyOn(output.instance(), 'syncRenderEdge');
      output.setProps({
        edges: [{ source: 'a', target: 'b' }],
        nodes: [
          { id: 'a' },
          { id: 'b' },
          { id: 'c' },
        ],
      });
    });

    it('does nothing when not dragging an edge', () => {
      output.instance().handleZoomEnd();
      expect(GraphUtils.removeElementFromDom).not.toHaveBeenCalled();
    });

    it('does nothing when there is no dragged edge object', () => {
      output.setState({
        draggingEdge: true,
      });
      output.instance().handleZoomEnd();
      expect(GraphUtils.removeElementFromDom).not.toHaveBeenCalled();
    });

    it('drags an edge', () => {
      const draggedEdge = {
        source: 'a',
        target: 'b',
      };
      output.setState({
        draggedEdge,
        draggingEdge: true,
        edgeEndNode: { id: 'c' },
      });
      output.instance().handleZoomEnd();
      expect(GraphUtils.removeElementFromDom).toHaveBeenCalled();
      expect(output.state().draggedEdge).toEqual(null);
      expect(output.state().draggingEdge).toEqual(false);
      expect(output.instance().syncRenderEdge).toHaveBeenCalled();
      expect(onSwapEdge).toHaveBeenCalled();
    });

    it('handles swapping the edge to a different node', () => {
      const draggedEdge = {
        source: 'a',
        target: 'b',
      };
      output.setState({
        draggedEdge,
        draggingEdge: true,
        edgeEndNode: { id: 'c' },
      });
      output.instance().handleZoomEnd();
      expect(output.instance().syncRenderEdge).toHaveBeenCalledWith({ source: 'a', target: 'c' });
    });
  });

  describe('handleZoom method', () => {
    beforeEach(() => {
      jest.spyOn(output.instance(), 'dragEdge');
      jest.spyOn(output.instance(), 'renderGraphControls');
      d3.event = {
        transform: 'test',
      };
      output.instance().view = document.createElement('g');
    });

    it('handles the zoom event when a node is not hovered nor an edge is being dragged', () => {
      output.instance().handleZoom();
      expect(output.instance().renderGraphControls).toHaveBeenCalled();
      expect(output.instance().dragEdge).not.toHaveBeenCalled();
    });

    it('does nothing when the zoom level hasn\'t changed', () => {
      output.setState({
        viewTransform: 'test',
      });
      output.instance().handleZoom();
      expect(output.instance().renderGraphControls).not.toHaveBeenCalled();
      expect(output.instance().dragEdge).not.toHaveBeenCalled();
    });

    it('deals with dragging an edge', () => {
      output.setState({
        draggingEdge: true,
      });
      output.instance().handleZoom();
      expect(output.instance().renderGraphControls).not.toHaveBeenCalled();
      expect(output.instance().dragEdge).toHaveBeenCalled();
    });

    it('zooms when a node is hovered', () => {
      output.setState({
        hoveredNode: {},
      });
      output.instance().handleZoom();
      expect(output.instance().renderGraphControls).toHaveBeenCalled();
      expect(output.instance().dragEdge).not.toHaveBeenCalled();
    });
  });

  describe('dragEdge method', () => {
    let draggedEdge;
    beforeEach(() => {
      draggedEdge = {
        source: 'a',
        target: 'b',
      };
      jest.spyOn(output.instance(), 'syncRenderEdge');
      output.instance().selectedView = d3.select(document.createElement('g'));
      d3.mouse = jest.fn().mockReturnValue([5, 15]);
      output.setProps({
        nodes: [
          { id: 'a', x: 5, y: 10 },
          { id: 'b', x: 10, y: 20 },
        ],
      });
      output.setState({
        draggedEdge,
      });
    });

    it('does nothing when an edge is not dragged', () => {
      output.setState({
        draggedEdge: null,
      });
      output.instance().dragEdge();
      expect(output.instance().syncRenderEdge).not.toHaveBeenCalled();
    });

    it('drags the edge', () => {
      output.instance().dragEdge();
      expect(output.instance().syncRenderEdge).toHaveBeenCalledWith({
        source: draggedEdge.source,
        targetPosition: { x: 5, y: 15 },
      });
    });
  });

  describe('handleZoomStart method', () => {
    let edge;
    beforeEach(() => {
      jest.spyOn(output.instance(), 'isArrowClicked').mockReturnValue(true);
      edge = { source: 'a', target: 'b' };
      output.setProps({
        edges: [edge],
      });
      d3.event = {
        sourceEvent: {
          target: {
            classList: {
              contains: jest.fn().mockReturnValue(true),
            },
            id: 'a_b',
          },
          buttons: 0,
        },
      };
    });

    it('does nothing when the graph is readOnly', () => {
      output.setProps({
        readOnly: true,
      });
      output.instance().dragEdge = jest.fn();
      output.instance().handleZoomStart();
      expect(output.instance().dragEdge).not.toHaveBeenCalled();
    });

    it('does nothing when there is no sourceEvent', () => {
      d3.event = {
        sourceEvent: null,
      };
      output.instance().dragEdge = jest.fn();
      output.instance().handleZoomStart();
      expect(output.instance().dragEdge).not.toHaveBeenCalled();
    });

    it('does nothing when the sourceEvent is not an edge', () => {
      d3.event.sourceEvent.target.classList.contains.mockReturnValue(false);
      output.instance().dragEdge = jest.fn();
      output.instance().handleZoomStart();
      expect(output.instance().dragEdge).not.toHaveBeenCalled();
    });

    it('does nothing if the arrow wasn\'t clicked', () => {
      output.instance().isArrowClicked.mockReturnValue(false);
      output.instance().dragEdge = jest.fn();
      output.instance().handleZoomStart();
      expect(output.instance().dragEdge).not.toHaveBeenCalled();
    });

    it('does nothing if there is no edge', () => {
      d3.event.sourceEvent.target.id = 'fake';
      output.instance().dragEdge = jest.fn();
      output.instance().handleZoomStart();
      expect(output.instance().dragEdge).not.toHaveBeenCalled();
    });

    it('drags the edge', () => {
      d3.event.sourceEvent.buttons = 2;
      d3.mouse = jest.fn().mockReturnValue([1, 2]);
      output.instance().dragEdge = jest.fn();
      output.instance().handleZoomStart();
      expect(output.state().draggedEdge).toEqual(edge);
      expect(output.instance().dragEdge).toHaveBeenCalled();
    });
  });
});
