// @flow
import None from './none';

describe('None', () => {
  const snapToGrid = {
    edges: [],
    edgeTypes: {},
    nodes: [],
    selected: null,
    onCreateNode: jest.fn(),
    onCreateEdge: jest.fn(),
    onDeleteNode: jest.fn(),
    onDeleteEdge: jest.fn(),
    onSelectNode: jest.fn(),
    onSelectEdge: jest.fn(),
    onSwapEdge: jest.fn(),
    onUpdateNode: jest.fn(),
    zoomControlsRef: {
      current: document.createElement('div'),
    },
    ACTION_SAVE_MAP_TO_UNDO: jest.fn(),
    gridSpacing: 10,
  };
  describe('class', () => {
    it('is defined', () => {
      expect(None).toBeDefined();
    });

    it('instantiates', () => {
      const blah = new None(snapToGrid);
      expect(blah).toBeDefined();
    });
  });
});
