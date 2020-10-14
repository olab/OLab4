// @flow
import GraphUtils from '.';

describe('GraphUtils class', () => {
  describe('getNodesMap method', () => {
    it('converts an array of nodes to a hash map', () => {
      const nodes = [{
        id: 'foo',
        name: 'bar',
      }];
      const nodesMap = GraphUtils.getNodesMap(nodes);

      expect(JSON.stringify(nodesMap)).toEqual(JSON.stringify({
        'key-foo': {
          children: [],
          incomingEdges: [],
          node: nodes[0],
          originalArrIndex: 0,
          outgoingEdges: [],
          parents: [],
        },
      }));
    });
  });

  describe('getEdgesMap method', () => {
    it('converts an array of edges to a hash map', () => {
      const edges = [{
        source: 'foo',
        target: 'bar',
      }];
      const edgesMap = GraphUtils.getEdgesMap(edges);

      expect(JSON.stringify(edgesMap)).toEqual(JSON.stringify({
        foo_bar: {
          edge: edges[0],
          originalArrIndex: 0,
        },
      }));
    });
  });

  describe('linkNodesAndEdges method', () => {
    let nodesMap;

    beforeEach(() => {
      nodesMap = {
        'key-bar': {
          children: [],
          incomingEdges: [],
          node: { id: 'bar' },
          originalArrIndex: 0,
          outgoingEdges: [],
          parents: [],
        },
        'key-foo': {
          children: [],
          incomingEdges: [],
          node: { id: 'foo' },
          originalArrIndex: 0,
          outgoingEdges: [],
          parents: [],
        },
      };
    });

    it('fills in various properties of a nodeMapNode', () => {
      const edges = [{
        source: 'foo',
        target: 'bar',
      }];
      GraphUtils.linkNodesAndEdges(nodesMap, edges);

      expect(nodesMap['key-bar'].incomingEdges.length).toEqual(1);
      expect(nodesMap['key-bar'].incomingEdges[0]).toEqual(edges[0]);
      expect(nodesMap['key-foo'].outgoingEdges.length).toEqual(1);
      expect(nodesMap['key-foo'].outgoingEdges[0]).toEqual(edges[0]);
      expect(nodesMap['key-foo'].children.length).toEqual(1);
      expect(nodesMap['key-foo'].children[0]).toEqual(nodesMap['key-bar']);
      expect(nodesMap['key-bar'].parents.length).toEqual(1);
      expect(nodesMap['key-bar'].parents[0]).toEqual(nodesMap['key-foo']);
    });

    it('does not modify nodes if there is no matching target', () => {
      const edges = [{
        source: 'foo',
        target: 'fake',
      }];
      GraphUtils.linkNodesAndEdges(nodesMap, edges);

      expect(nodesMap['key-foo'].outgoingEdges.length).toEqual(0);
      expect(nodesMap['key-foo'].children.length).toEqual(0);
    });

    it('does not modify nodes if there is no matching source', () => {
      const edges = [{
        source: 'fake',
        target: 'bar',
      }];
      GraphUtils.linkNodesAndEdges(nodesMap, edges);

      expect(nodesMap['key-bar'].incomingEdges.length).toEqual(0);
      expect(nodesMap['key-bar'].parents.length).toEqual(0);
    });
  });

  describe('removeElementFromDom method', () => {
    it('removes an element using an id', () => {
      const fakeElement = {
        parentNode: {
          removeChild: jest.fn(),
        },
      };
      jest.spyOn(document, 'getElementById').mockReturnValue(fakeElement);
      const result = GraphUtils.removeElementFromDom('fake');

      expect(fakeElement.parentNode.removeChild).toHaveBeenCalledWith(fakeElement);
      expect(result).toEqual(true);
    });

    it('does nothing when it can\'t find the element', () => {
      jest.spyOn(document, 'getElementById').mockReturnValue(undefined);
      const result = GraphUtils.removeElementFromDom('fake');
      expect(result).toEqual(false);
    });
  });

  describe('findParent method', () => {
    it('returns the element if an element matches a selector', () => {
      const testItem = {};
      const element = document.createElement('div');
      element.closest = jest.fn().mockReturnValue(testItem);
      const parent = GraphUtils.findParent(element, 'fake');
      expect(parent).toEqual(testItem);
    });

    it('returns null when there is no match', () => {
      const element = {
        parentNode: {
          matches: jest.fn().mockReturnValue(false),
        },
      };
      const parent = GraphUtils.findParent(element, 'fake');
      expect(parent).toEqual(null);
    });
  });
});
