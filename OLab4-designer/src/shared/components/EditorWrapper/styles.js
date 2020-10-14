import styled from 'styled-components';
import {
  LIGHT_BLUE, DARK_BLUE, WHITE, MIDDLE_GREY,
} from '../../colors';

export const HeadingWrapper = styled.div`
  display: flex;
  align-items: center;
  width: 100%;
  position: sticky;
  z-index: 3;
  top: 0;
  justify-content: space-between;
  padding: 12px 18px 12px 6px;
  background-color: ${WHITE};
  border-bottom-width: 1px;
  border-bottom-style: solid;
  border-bottom-color: ${MIDDLE_GREY};
`;

export const Paper = styled.div`
  display: flex;
  flex-direction: column;
  padding: 12px 18px;
  boxShadow: none;
  width: 60%;

  @media only screen and (max-width: 1099px) {
    width: 100%;
  }
`;

export const Container = styled.div`
  width: 100%;
  height: 83vh;
  padding-bottom: 70px;
  overflow: auto;
`;

const styles = () => ({
  root: {
    display: 'flex',
    flexDirection: 'column',
    height: '100vh',
  },
  leftPanel: {
    backgroundColor: LIGHT_BLUE,
  },
  headerLabel: {
    display: 'flex',
    alignItems: 'center',
  },
  rightPanel: {
    boxShadow: 'none',
  },
  title: {
    color: `${DARK_BLUE}`,
    fontWeight: 600,
  },
  arrow: {
    fill: `${DARK_BLUE}`,
  },
  submit: {
    marginTop: 0,
    float: 'right',
    bottom: 0,
    width: 160,
  },
});

export default styles;
