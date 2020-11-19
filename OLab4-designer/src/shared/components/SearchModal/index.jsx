// @flow
import React, { PureComponent } from 'react';

import ConfirmationModal from '../ConfirmationModal';
import filterByIndex from '../../../helpers/filterByIndex';
import filterByName from '../../../helpers/filterByName';
import ListWithSearch from '../ListWithSearch';
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
    const scopedObjectsNameFiltered = filterByName(items, query);
    const scopedObjectsIndexFiltered = filterByIndex(items, query);
    const listFiltered = [...scopedObjectsNameFiltered, ...scopedObjectsIndexFiltered];

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
      iconEven,
      iconOdd,
      isItemsFetching,
      items,
      label,
      onClose,
      onItemChoose,
      searchLabel,
      text,
    } = this.props;

    const isHideSearch = isItemsFetching && !items.length;

    return (
      <ConfirmationModal
        label={label}
        text={text}
        onClose={onClose}
      >
        <ListWithSearch
          iconEven={iconEven}
          iconOdd={iconOdd}
          innerRef={this.setListWithSearchRef}
          isForModal
          isHideSearch={isHideSearch}
          isItemsFetching={isItemsFetching}
          label={searchLabel}
          list={listFiltered}
          onClear={this.clearSearchInput}
          onItemClick={onItemChoose}
          onSearch={this.handleItemsSearch}
        />
      </ConfirmationModal>
    );
  }
}

export default SearchModal;
