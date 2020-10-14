// @flow
import React, { PureComponent } from 'react';

import type { IExpandableItemProps, IExpandableItemState } from './types';

import { ExpandableItemStyle } from './styles';

class ExpandableItem extends PureComponent<IExpandableItemProps, IExpandableItemState> {
  expandableItemRef: { current: null | Element };

  constructor(props: IExpandableItemProps) {
    super(props);
    this.state = {
      isCollapsed: false,
      isOpen: false,
    };

    this.expandableItemRef = React.createRef();
  }

  componentDidMount() {
    if (this.isEllipsisActive) {
      this.collapse();
    }
  }

  get isEllipsisActive(): boolean {
    const { current: expandableItem } = this.expandableItemRef;

    return expandableItem.offsetWidth < expandableItem.scrollWidth;
  }

  collapse = (): void => {
    this.setState({ isCollapsed: true });
  }

  toggleOpen = (): void => {
    this.setState(({ isOpen }) => ({
      isOpen: !isOpen,
    }));
  }

  render() {
    const { isCollapsed, isOpen } = this.state;
    const { children } = this.props;

    return (
      <ExpandableItemStyle
        ref={this.expandableItemRef}
        isOpen={isOpen}
        isCollapsed={isCollapsed}
        onClick={isCollapsed ? this.toggleOpen : null}
      >
        {children}
      </ExpandableItemStyle>
    );
  }
}

export default ExpandableItem;
