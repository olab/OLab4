// @flow
import React from 'react';
import { shallow } from 'enzyme';

import { ArrowHead } from '.';

describe('<ArrowHead />', () => {
  let output = {};
  let edgeArrowSize;

  beforeEach(() => {
    edgeArrowSize = 0;
    output = shallow(
      <ArrowHead
        edgeArrowSize={edgeArrowSize}
      />,
    );
  });

  describe('component is rendered', () => {
    it('renders w/ \'edgeArrowSize\' = null', () => {
      output.setProps({
        edgeArrowSize: null,
      });

      expect(output.getElement()).toBeNull();
    });

    it('renders w/ \'edgeArrowSize\' != null', () => {
      output.setProps({
        edgeArrowSize: 2,
      });
      expect(output.getElement()).not.toBeNull();

      expect(output.props().refX).toEqual(1);
      expect(output.props().viewBox).toEqual('0 -1 2 2');
    });
  });
});
