// @flow
import React from 'react';
import { shallow } from 'enzyme';

import NodeComponent from '.';

describe('<NodeComponent />', () => {
  let output = {};
  beforeEach(() => {
    output = shallow(
      <NodeComponent
        classes={{}}
      />,
    );
  });

  it('renders successfully', () => {
    expect(output.getElement()).not.toBeNull();
  });
});
