// @flow
import React, { PureComponent } from 'react';
import { withStyles } from '@material-ui/core/styles';
import classNames from 'classnames';
import {
  Close as CloseIcon,
  Search as SearchIcon,
  Delete as DeleteIcon,
  FilterVintage as DefaultIcon,
  FilterVintageOutlined as DefaultOutlinedIcon,
} from '@material-ui/icons';
import {
  List, ListItem, ListItemText, Button, IconButton, TextField, Typography, Tooltip,
} from '@material-ui/core';

import CircularSpinnerWithText from '../CircularSpinnerWithText';

// import getIconType from '../../../helpers/getIconType';
import removeHTMLTags from '../../../helpers/removeHTMLTags';

import type { IListWithSearchProps, IListWithSearchState } from './types';

import styles, { SearchWrapper, ListItemContentWrapper } from './styles';

class ListWithSearch extends PureComponent<IListWithSearchProps, IListWithSearchState> {
  static defaultProps = {
    getIcon: () => '',
    getIconTooltip: () => '',
    iconEven: DefaultIcon,
    iconOdd: DefaultOutlinedIcon,
    isForModal: false,
    isItemsDisabled: false,
    isWithSpinner: true,
    primarytext: (item) => item.name,
    secondarytext: (item) => item.description,
    showIcons: true,
  };

  state: IListWithSearchState = {
    query: '',
  };

  clearSearch = (): void => {
    const { onClear } = this.props;

    onClear();
    this.setState({ query: '' });
  }

  onInputChange = (e: Event): void => {
    const { onSearch } = this.props;
    const { name, value } = (e.target: window.HTMLInputElement);

    onSearch(value);
    this.setState({ [name]: value });
  }

  render() {
    const { query } = this.state;
    const {
      classes,
      getIcon,
      getIconTooltip,
      isForModal,
      isHideSearch,
      isItemsDisabled,
      isItemsFetching,
      isWithSpinner,
      label,
      list,
      onItemClick,
      onItemDelete,
      primarytext,
      secondarytext,
      showIcons,
    } = this.props;

    const listClassNames = classNames(
      classes.list,
      { [classes.listLimits]: isForModal },
      { [classes.listEmpty]: isHideSearch },
    );

    const isShowSpinner = isWithSpinner && isItemsFetching;

    return (
      <div>
        {!isHideSearch && (
          <SearchWrapper>
            <TextField
              type="search"
              name="query"
              label={label}
              className={classes.searchField}
              value={query}
              onChange={this.onInputChange}
              fullWidth
            />

            {query.trim() ? (
              <IconButton
                aria-label="Clear Input"
                title="Clear Input"
                onClick={this.clearSearch}
                classes={{ root: classes.searchIcon }}
              >
                <CloseIcon />
              </IconButton>
            ) : (
              <SearchIcon
                classes={{ root: classes.searchIcon }}
              />
            )}
          </SearchWrapper>
        )}

        <List
          classes={{ root: listClassNames }}
          disablePadding
        >
          {list.map((listItem) => (
            <ListItem
              key={listItem.id}
              classes={{ root: classes.listItem }}
              disabled={isItemsDisabled}
            >
              <ListItemContentWrapper>
                <Tooltip title={getIconTooltip(showIcons, listItem)}>
                  <Button
                    classes={{ text: classes.listButton }}
                    onClick={() => onItemClick(listItem)}
                    disabled={isItemsDisabled}
                  >
                    {getIcon(showIcons, listItem)}
                    &nbsp;
                    <ListItemText
                      primary={primarytext(listItem)}
                      secondary={removeHTMLTags(secondarytext(listItem) || '')}
                      classes={{ secondary: classes.secondaryText }}
                    />
                  </Button>
                </Tooltip>
                {onItemDelete && (
                  <IconButton
                    size="small"
                    title={`Delete ${listItem.name}`}
                    aria-label="Delete Scoped Object"
                    onClick={() => onItemDelete(listItem.id)}
                    classes={{ root: classes.deleteIcon }}
                    disabled={isItemsDisabled}
                  >
                    <DeleteIcon />
                  </IconButton>
                )}
              </ListItemContentWrapper>
            </ListItem>
          ))}

          {!list.length && (
            <ListItem classes={{ root: classes.listItem }}>
              <Typography align="right" variant="caption">
                Empty list...
              </Typography>
            </ListItem>
          )}
        </List>

        {isShowSpinner && <CircularSpinnerWithText text="Updating list from the server..." centered large />}
      </div>
    );
  }
}

export default withStyles(styles)(ListWithSearch);
