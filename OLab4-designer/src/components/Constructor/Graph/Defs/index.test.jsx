// @flow
import React from 'react';
import { shallow } from 'enzyme';

import { Defs } from '.';

const StandardEdgeShape = (
  <symbol width="24" height="24" viewBox="0 0 24 24">
    <circle r="12" transform="matrix(1 0 0 -1 12 12)" fill="#D3DAE1" />

    <g transform="translate(18, 11) rotate(90)">
      <path d="M1 13L1 1" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </g>
  </symbol>
);

describe('<Defs />', () => {
  let output = {};
  let edgeTypes;

  beforeEach(() => {
    edgeTypes = {
      standardEdge: {
        shape: StandardEdgeShape,
        shapeId: '#standardEdge',
      },
      standardEdge2: {
        shape: StandardEdgeShape,
        shapeId: '#standardEdge2',
      },
    };
    output = shallow(
      <Defs
        edgeTypes={edgeTypes}
      />,
    );
  });

  describe('component is rendered', () => {
    it('rendered', () => {
      expect(output.getElement()).not.toBeNull();
      expect(output.state().graphConfigDefs.length).toEqual(2);
      expect(output.children().length).toEqual(4);
    });
  });
});
