// @flow
import React from 'react';
import { shallow } from 'enzyme';

import { Circle } from '.';

describe('<Circle />', () => {
  let output = {};

  beforeEach(() => {
    output = shallow(<Circle />);
  });

  describe('component is rendered', () => {
    it('rendered', () => {
      expect(output.getElement()).not.toBeNull();

      expect(output.props().cx).toEqual(2);
      expect(output.props().cy).toEqual(2);
      expect(output.props().r).toEqual(2);
    });
    it('rendered with other props', () => {
      output.setProps({
        gridDotSize: 5,
      });

      expect(output.props().cx).toEqual(5);
      expect(output.props().cy).toEqual(5);
      expect(output.props().r).toEqual(5);
    });
  });
});
