// @flow
import React, { PureComponent } from 'react';
import { withStyles } from '@material-ui/core/styles';
import {
  Grid, Button, Paper, Typography, Divider,
} from '@material-ui/core';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import * as scopedObjectsActions from '../../../redux/scopedObjects/action';
import { ListWithSearchWrapper } from '../styles';
import { PAGE_TITLES, SCOPED_OBJECTS } from '../../config';
import { toLowerCaseAndPlural, toUpperCaseAndPlural } from '../utils';
import capitalizeFirstLetter from '../../../helpers/capitalizeFirstLetter';
import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';
import filterByName from '../../../helpers/filterByName';
import filterByIndex from '../../../helpers/filterByIndex';
import { getIconType, getQuestionIconType } from '../../../helpers/getIconType';
import ListWithSearch from '../../../shared/components/ListWithSearch';
import styles, { HeaderWrapper, ProgressWrapper } from './styles';
import type { ISOListProps, ISOListState } from './types';
import type { ScopedObjectListItem as ScopedObjectListItemType } from '../../../redux/scopedObjects/types';

class SOList extends PureComponent<ISOListProps, ISOListState> {
  listWithSearchRef: null | React.RefObject<any>;

  SOTypeLowerCasedAndPluralled: string;

  SOTypeUpperCasedAndPluralled: string;

  constructor(props: ISOListProps) {
    super(props);
    this.state = {
      scopedObjectsFiltered: props.scopedObjects,
    };

    this.listWithSearchRef = React.createRef();

    const {
      match: { params: { scopedObjectType } },
      ACTION_SCOPED_OBJECTS_TYPED_REQUESTED,
    } = props;

    this.SOTypeLowerCasedAndPluralled = toLowerCaseAndPlural(scopedObjectType);
    this.SOTypeUpperCasedAndPluralled = toUpperCaseAndPlural(scopedObjectType);
    ACTION_SCOPED_OBJECTS_TYPED_REQUESTED(this.SOTypeLowerCasedAndPluralled);
  }

  componentDidUpdate(prevProps: ISOListProps) {
    const {
      scopedObjects,
      match: { params: { scopedObjectType } },
      ACTION_SCOPED_OBJECTS_TYPED_REQUESTED,
    } = this.props;
    const {
      scopedObjects: scopedObjectsPrev,
      match: { params: { scopedObjectType: scopedObjectTypePrev } },
    } = prevProps;
    const { query } = this.listWithSearchRef.state;

    this.setPageTitle();

    if (scopedObjectType !== scopedObjectTypePrev) {
      this.SOTypeLowerCasedAndPluralled = toLowerCaseAndPlural(scopedObjectType);
      this.SOTypeUpperCasedAndPluralled = toUpperCaseAndPlural(scopedObjectType);

      ACTION_SCOPED_OBJECTS_TYPED_REQUESTED(this.SOTypeLowerCasedAndPluralled);
    }

    if (scopedObjects !== scopedObjectsPrev) {
      const scopedObjectsNameFiltered = filterByName(scopedObjects, query);
      const scopedObjectsIndexFiltered = filterByIndex(scopedObjects, query);
      const scopedObjectsFiltered = [...scopedObjectsNameFiltered, ...scopedObjectsIndexFiltered];

      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ scopedObjectsFiltered });
    }
  }

  setPageTitle = (): void => {
    const {
      match: { params: { scopedObjectType } },
    } = this.props;

    document.title = PAGE_TITLES.SO_LIST(capitalizeFirstLetter(scopedObjectType));
  }

  getIcon = (showIcons, scopedObject) => {
    if (showIcons) {
      if (Object.prototype.hasOwnProperty.call(scopedObject, 'questionType')) {
        const MediaIconContent = getQuestionIconType(scopedObject.questionType);
        return <MediaIconContent />;
      }

      if (Object.prototype.hasOwnProperty.call(scopedObject, 'resourceUrl')) {
        const iconType = scopedObject.resourceUrl && scopedObject.resourceUrl.split('.').pop();
        const MediaIconContent = getIconType(iconType);
        return <MediaIconContent />;
      }
    }

    return '';
  };

  searchObjectList = (query: string): void => {
    const { scopedObjects } = this.props;
    const scopedObjectsNameFiltered = filterByName(scopedObjects, query);
    const scopedObjectsIndexFiltered = filterByIndex(scopedObjects, query);
    const scopedObjectsFiltered = [...scopedObjectsNameFiltered, ...scopedObjectsIndexFiltered];

    this.setState({ scopedObjectsFiltered });
  }

  clearSearchInput = (): void => {
    const { scopedObjects } = this.props;
    const scopedObjectsFiltered = scopedObjects;

    this.setState({ scopedObjectsFiltered });
  }

  onObjectClick = (scopedObject: ScopedObjectListItemType): void => {
    const { history, pathname } = this.props;
    history.push(`${pathname}/${scopedObject.id}`);
  }

  setListWithSearchRef = (ref: any): void => {
    this.listWithSearchRef = ref;
  }

  onClickObjectDelete = (scopedObjectId: number): void => {
    const { ACTION_SCOPED_OBJECT_DELETE_REQUESTED } = this.props;
    ACTION_SCOPED_OBJECT_DELETE_REQUESTED(
      scopedObjectId,
      this.SOTypeLowerCasedAndPluralled,
    );
  }

  primarytext = (scopedObject) => {
    const {
      match: { params: { scopedObjectType } },
    } = this.props;

    if (scopedObjectType === 'question') {
      return scopedObject.stem;
    }

    return scopedObject.name;
  }

  secondarytext = (scopedObject) => {
    const {
      match: { params: { scopedObjectType } },
    } = this.props;

    if (scopedObjectType === 'question') {
      return `Id: ${scopedObject.id}`;
    }

    return scopedObject.description;
  }

  handleRedirect = () => {
    const { history, pathname } = this.props;
    history.push(`${pathname}/add`);
  }

  render() {
    const { scopedObjectsFiltered } = this.state;
    const {
      classes,
      scopedObjects,
      isScopedObjectsFetching,
      match: { params: { scopedObjectType } },
    } = this.props;

    const isHideSearch = isScopedObjectsFetching && !scopedObjects.length;
    const isMedia = scopedObjectType === SCOPED_OBJECTS.FILE.name.toLowerCase();
    const searchLabel = `Search for ${scopedObjectType} by keyword or index`;

    return (
      <Grid container component="main" className={classes.root}>
        <Grid item xs={12} sm={11} md={11} component={Paper} className={classes.rightPanel}>
          <HeaderWrapper>
            <Typography variant="h4" className={classes.title}>
              {this.SOTypeUpperCasedAndPluralled}
            </Typography>
            <ProgressWrapper>
              <Button
                color="primary"
                variant="contained"
                className={classes.button}
                onClick={this.handleRedirect}
              >
                {`Add New ${scopedObjectType}`}
              </Button>
              {isScopedObjectsFetching && (
                <CircularSpinnerWithText text="Updating list from the server..." />
              )}
            </ProgressWrapper>
          </HeaderWrapper>
          <Divider />
          <ListWithSearchWrapper>
            <ListWithSearch
              getIcon={this.getIcon}
              innerRef={this.setListWithSearchRef}
              isHideSearch={isHideSearch}
              isItemsFetching={isScopedObjectsFetching}
              isMedia={isMedia}
              isWithSpinner={false}
              label={searchLabel}
              primarytext={this.primarytext}
              secondarytext={this.secondarytext}
              list={scopedObjectsFiltered}
              onClear={this.clearSearchInput}
              onItemClick={this.onObjectClick}
              onItemDelete={this.onClickObjectDelete}
              onSearch={this.searchObjectList}
            />
          </ListWithSearchWrapper>
        </Grid>
      </Grid>
    );
  }
}

const mapStateToProps = (
  { scopedObjects },
  {
    match: { params: { scopedObjectType } },
    location: { pathname },
  },
) => ({
  pathname,
  scopedObjects: scopedObjects[toLowerCaseAndPlural(scopedObjectType)],
  isScopedObjectsFetching: scopedObjects.isFetching,
});

const mapDispatchToProps = dispatch => ({
  ACTION_SCOPED_OBJECTS_TYPED_REQUESTED: (scopedObjectType: string) => {
    dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECTS_TYPED_REQUESTED(scopedObjectType));
  },
  ACTION_SCOPED_OBJECT_DELETE_REQUESTED: (
    scopedObjectId: number,
    scopedObjectType: string,
  ) => {
    dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECT_DELETE_REQUESTED(
      scopedObjectId,
      scopedObjectType,
    ));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  withStyles(styles)(
    withRouter(SOList),
  ),
);
