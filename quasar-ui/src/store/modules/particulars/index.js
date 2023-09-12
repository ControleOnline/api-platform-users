/* eslint-disable */
import * as actions from './actions';
import * as getters from './getters';
import mutations from './mutations';

export default {
  namespaced: true,
  state     : {
    isLoading : false,
    error     : '',
    violations: null,
    retrieved : null,
    totalItems: 10,
    items     : [],
    view      : [],
  },
  actions,
  getters,
  mutations,
};
