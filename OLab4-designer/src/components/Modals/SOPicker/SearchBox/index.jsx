// @flow
import React, { PureComponent } from 'react';
import { withStyles } from '@material-ui/core/styles';
import { IconButton } from '@material-ui/core';

import RedCrossIcon from '../../../../shared/assets/icons/red_cross.svg';
import SearchIcon from '../../../../shared/assets/icons/search.svg';

import type { ISearchBoxProps, ISearchBoxState } from './types';

import styles, { SearchBoxWrapper, SearchIconWrapper } from './styles';

class SearchBox extends PureComponent<ISearchBoxProps, ISearchBoxState> {
  searchInputRef: { current: null | HTMLInputElement };

  constructor(props: ISearchBoxProps) {
    super(props);
    this.state = {
      value: '',
    };

    this.searchInputRef = React.createRef();
  }

  handleValueChange = (e: Event): void => {
    const { onSearch } = this.props;
    const { value } = (e.target: window.HTMLInputElement);

    onSearch(value);
    this.setState({ value });
  }

  resetValue = () => {
    const { onSearch } = this.props;
    const value = '';

    onSearch(value);
    this.setState({ value });

    if (this.searchInputRef && this.searchInputRef.current) {
      this.searchInputRef.current.focus();
    }
  }

  render() {
    const { value } = this.state;
    const { classes } = this.props;

    return (
      <SearchBoxWrapper>
        <SearchIconWrapper>
          <SearchIcon />
        </SearchIconWrapper>
        <input
          ref={this.searchInputRef}
          name="search"
          type="search"
          value={value}
          placeholder="Search files"
          autoComplete="off"
          onChange={this.handleValueChange}
        />
        {!!value && (
          <IconButton
            classes={{ root: classes.iconButton }}
            onClick={this.resetValue}
          >
            <RedCrossIcon />
          </IconButton>
        )}
      </SearchBoxWrapper>
    );
  }
}

export default withStyles(styles)(SearchBox);
