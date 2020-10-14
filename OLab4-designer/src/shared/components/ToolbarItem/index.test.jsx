// @flow
import React from 'react';
import { shallow } from 'enzyme';

import ToolbarItem from '.';

describe('<ToolbarItem />', () => {
  let output = {};
  let id;
  let name;
  let icon;
  let mouseIcon;
  let order;
  let label;
  beforeEach(() => {
    id = 'id';
    name = 'name';
    icon = 'icon';
    mouseIcon = 'mouseIcon';
    order = 0;
    label = 'label';
    output = shallow(
      <ToolbarItem
        id={id}
        name={name}
        icon={icon}
        mouseIcon={mouseIcon}
        order={order}
        label={label}
      />,
    );
  });

  it('renders successfully', () => {
    expect(output.getElement()).not.toBeNull();
  });
});
