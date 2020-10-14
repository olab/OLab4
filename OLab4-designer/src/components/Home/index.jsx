// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import {
  Spa as MapsIcon,
  SpaOutlined as MapsOutlinedIcon,
  Dashboard as TemplateIcon,
  DashboardOutlined as TemplateOutlinedIcon,
} from '@material-ui/icons';

import ExpansionPanel from './ExpansionPanel';
import SearchModal from '../../shared/components/SearchModal';
import ListWithSearch from '../../shared/components/ListWithSearch';

import * as mapActions from '../../redux/map/action';
import * as wholeMapActions from '../../middlewares/app/action';
import * as templatesActions from '../../redux/templates/action';
import * as scopedLevelsActions from '../../redux/scopeLevels/action';

import type { IHomeProps, IHomeState } from './types';
import type { Template as TemplateType } from '../../redux/templates/types';
import type { ScopeLevel as ScopeLevelType } from '../../redux/scopeLevels/types';

import { PAGE_TITLES, SCOPE_LEVELS } from '../config';

import filterByName from '../../helpers/filterByName';

import { HomeWrapper, HomeHeader, MapListWrapper } from './styles';

class Home extends PureComponent<IHomeProps, IHomeState> {
  listWithSearchRef: null | React.RefObject<any>;

  constructor(props: IHomeProps) {
    super(props);
    this.state = {
      mapsFiltered: props.maps,
      isButtonsDisabled: false,
      isShowTemplatesListModal: false,
    };

    this.listWithSearchRef = React.createRef();

    this.setPageTitle();

    props.ACTION_MAPS_REQUESTED();
  }

  componentDidUpdate(prevProps: IHomeProps) {
    const {
      mapId, maps, history, isMapDetailsFetching,
    } = this.props;
    const {
      maps: mapsPrev, isMapDetailsFetching: isMapDetailsFetchingPrev,
    } = prevProps;

    const isFetchingStopped = isMapDetailsFetchingPrev && !isMapDetailsFetching;
    const isMapRetrieved = isFetchingStopped && mapId;

    if (isFetchingStopped) {
      this.toggleDisableButtons();
    }

    if (maps !== mapsPrev) {
      const { query } = this.listWithSearchRef.state;
      const mapsFiltered = filterByName(maps, query);

      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ mapsFiltered });
    }

    if (isMapRetrieved) {
      history.push(`/${mapId}`, { isFromHome: true });
    }
  }

  setPageTitle = (): void => {
    document.title = PAGE_TITLES.HOME;
  }

  handleTemplateChoose = (template: TemplateType): void => {
    const templateId = template ? template.id : null;

    const { ACTION_CREATE_MAP_REQUESTED } = this.props;
    ACTION_CREATE_MAP_REQUESTED(templateId);

    this.toggleDisableButtons();
  }

  toggleDisableButtons = (): void => {
    this.setState(({ isButtonsDisabled }) => ({
      isButtonsDisabled: !isButtonsDisabled,
    }));
  }

  showTemplatesListModal = (): void => {
    const { ACTION_TEMPLATES_REQUESTED } = this.props;
    ACTION_TEMPLATES_REQUESTED();

    this.setState({ isShowTemplatesListModal: true });
  }

  closeTemplatesListModal = (): void => {
    this.setState({ isShowTemplatesListModal: false });
  }

  handleItemsSearch = (query: string): void => {
    const { maps } = this.props;
    const mapsFiltered = filterByName(maps, query);

    this.setState({ mapsFiltered });
  }

  clearSearchInput = (): void => {
    const { maps: mapsFiltered } = this.props;
    this.setState({ mapsFiltered });
  }

  handleMapItemClick = (scopeLevel: ScopeLevelType): void => {
    const { ACTION_GET_WHOLE_MAP_MIDDLEWARE } = this.props;
    ACTION_GET_WHOLE_MAP_MIDDLEWARE(scopeLevel.id);

    this.toggleDisableButtons();
  }

  setListWithSearchRef = (ref: any): void => {
    this.listWithSearchRef = ref;
  }

  render() {
    const {
      mapsFiltered, isButtonsDisabled, isShowTemplatesListModal,
    } = this.state;
    const {
      templates, isMapsFetching, isTemplatesFetching,
    } = this.props;

    return (
      <HomeWrapper>
        <HomeHeader>Welcome home!</HomeHeader>

        <ExpansionPanel
          showModal={this.showTemplatesListModal}
          onChoose={this.handleTemplateChoose}
          isDisabled={isButtonsDisabled}
        />

        <MapListWrapper>
          <ListWithSearch
            label="Search for existing Maps"
            innerRef={this.setListWithSearchRef}
            onSearch={this.handleItemsSearch}
            onClear={this.clearSearchInput}
            onItemClick={this.handleMapItemClick}
            list={mapsFiltered}
            isItemsFetching={isMapsFetching}
            isItemsDisabled={isButtonsDisabled}
            iconEven={MapsIcon}
            iconOdd={MapsOutlinedIcon}
          />
        </MapListWrapper>

        {isShowTemplatesListModal && (
          <SearchModal
            label="Choose template"
            searchLabel="Search for template"
            text="Please take template from the following:"
            items={templates}
            onClose={this.closeTemplatesListModal}
            onItemChoose={this.handleTemplateChoose}
            isItemsFetching={isTemplatesFetching}
            iconEven={TemplateIcon}
            iconOdd={TemplateOutlinedIcon}
          />
        )}
      </HomeWrapper>
    );
  }
}

const mapStateToProps = ({
  mapDetails, templates, scopeLevels,
}) => ({
  mapId: mapDetails.id,
  maps: scopeLevels.maps,
  templates: templates.list,
  isMapDetailsFetching: mapDetails.isFetching,
  isMapsFetching: scopeLevels.isFetching,
  isTemplatesFetching: templates.isFetching,
});

const mapDispatchToProps = dispatch => ({
  ACTION_CREATE_MAP_REQUESTED: (templateId?: number) => {
    dispatch(mapActions.ACTION_CREATE_MAP_REQUESTED(templateId));
  },
  ACTION_GET_WHOLE_MAP_MIDDLEWARE: (mapId: number) => {
    dispatch(wholeMapActions.ACTION_GET_WHOLE_MAP_MIDDLEWARE(mapId));
  },
  ACTION_TEMPLATES_REQUESTED: () => {
    dispatch(templatesActions.ACTION_TEMPLATES_REQUESTED());
  },
  ACTION_MAPS_REQUESTED: () => {
    dispatch(scopedLevelsActions.ACTION_SCOPE_LEVELS_REQUESTED(
      SCOPE_LEVELS[0].toLowerCase(),
    ));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  withRouter(Home),
);
