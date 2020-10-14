// @flow
import React, { PureComponent } from 'react';

import ConfirmationModal from '../ConfirmationModal';
import ListWithSearch from '../ListWithSearch';

import filterByName from '../../../helpers/filterByName';

import type { ISearchModalProps, ISearchModalState } from './types';

class SearchModal extends PureComponent<ISearchModalProps, ISearchModalState> {
  listWithSearchRef: null | React.RefObject<any>;

  constructor(props: ISearchModalProps) {
    super(props);
    this.state = {
      listFiltered: props.items,
    };

    this.listWithSearchRef = React.createRef();
  }

  componentDidUpdate(prevProps: ISearchModalProps) {
    const { items } = this.props;
    const { query } = this.listWithSearchRef.state;

    if (items !== prevProps.items) {
      const listFiltered = filterByName(items, query);

      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ listFiltered });
    }
  }

  handleItemsSearch = (query: string): void => {
    const { items } = this.props;
    const listFiltered = filterByName(items, query);

    this.setState({ listFiltered });
  }

  clearSearchInput = (): void => {
    const { items } = this.props;
    this.setState({ listFiltered: items });
  }

  setListWithSearchRef = (ref: any): void => {
    this.listWithSearchRef = ref;
  }

  render() {
    const { listFiltered } = this.state;
    const {
      label,
      searchLabel,
      text,
      items,
      onClose,
      onItemChoose,
      iconEven,
      iconOdd,
      isItemsFetching,
    } = this.props;

    const isHideSearch = isItemsFetching && !items.length;

    return (
      <ConfirmationModal
        label={label}
        text={text}
        onClose={onClose}
      >
        <ListWithSearch
          label={searchLabel}
          innerRef={this.setListWithSearchRef}
          onSearch={this.handleItemsSearch}
          onClear={this.clearSearchInput}
          onItemClick={onItemChoose}
          list={listFiltered}
          iconEven={iconEven}
          iconOdd={iconOdd}
          isHideSearch={isHideSearch}
          isItemsFetching={isItemsFetching}
          isForModal
        />
      </ConfirmationModal>
    );
  }
}

export default SearchModal;
