import styled from 'styled-components';
import { WHITE, MIDDLE_GREY, MIDDLE_LIGHT_GREY } from '../../../../shared/colors';

export const Cell = styled.div`
  padding: 4px 4px 0 4px;
  display: flex;
  justify-content: space-between;
`;

const styles = () => ({
  textField: {
    marginBottom: 0,
    marginTop: 0,
    minWidth: 100,
    width: '100%',
  },
  firstColumnContainer: {
    display: 'flex',
    position: 'relative',
    width: 385,
  },
  firstColumn: {
    paddingRight: 15,
    paddingLeft: 15,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    fontWeight: 'normal',
    fontSize: '1.1rem',
  },
  cellContainer: {
    paddingTop: 5,
    paddingBottom: 7.5,
    paddingRight: 5,
    paddingLeft: 5,
  },
  cellContainerSticky: {
    position: 'sticky',
    width: 385,
    top: 0,
    left: 0,
    zIndex: 1,
    paddingRight: 0,
    backgroundColor: `${WHITE}`,
    '&:hover': {
      backgroundColor: `${MIDDLE_LIGHT_GREY}`,
    },
  },
  verticalDivider: {
    position: 'absolute',
    top: -6.5,
    right: 0,
    width: 1,
    height: 65,
    backgroundColor: `${MIDDLE_GREY}`,
  },
});

export default styles;
