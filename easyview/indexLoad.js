 /**
 * * Easyview primary file to load
 * *
 * * @package easyview report
 * * @copyright 2014 UC Regents
 * * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v2
 * */

	///////////////////////////////////////////////////
        //// define static part of columns for the grid////
        ///////////////////////////////////////////////////
        var COLUMNS = [  
			{ header: 'Name (filter) all students',         locked: true, width:150,dataIndex:'last', resizable:true,
                                renderer: function(v, cellValues, rec) {
                                        return rec.get('last').replace(/\\'/g,"'") + ', ' + rec.get('first').replace(/\\'/g,"'");
                                },
                                editor: {
                                        xtype: 'textfield'
                                },
                                items: {
                                        xtype: 'textfield',
                                        flex : 1,
                                        margin: 2,
                                        enableKeyEvents: true,
                                        listeners: {
                                                keyup: function() {
                                                        var store = this.up('tablepanel').store;
                                                        store.clearFilter();
							var group_filter_select = Ext.ComponentQuery.query('#group_filter_select')[0];
							group_filter_select.clearValue();
                                                        if (this.value) {
                                                                store.filter({property: 'name', value: this.value, anyMatch: true, caseSensitive: false});
                                                        }
                                                },
                                                buffer: 500
                                        }
                                },
				
                                summaryRenderer: function(value, summaryData, dataIndex) {
                                        return "Averages - Displayed"; 
                                },itemId:"info1"
                        }, 
                        { header: 'Perm',       dataIndex: 'perm',      locked: true,   hidden: hide_details, width:85,  itemId:"info2"}, 
                        { header: 'Email',      dataIndex: 'email',     locked: true,   hidden: hide_details, itemId:"info3" }, 
                        { header: 'Group(s)',      dataIndex: 'group',     locked: true,   hidden:hide_details,itemId:"info4" }, 
                        { header:'',      xtype:'actioncolumn',   locked: true,   width:40,       align:'center', 
                                items:[{        
                                        icon: 'resources/table.png',
                                        tooltip: 'Grade Report',
                                        handler: function(grid, rowIndex, colIndex) {
                                                var url =WROOT+"/grade/report/user/index.php?userid="+grid.panel.store.data.items[rowIndex].data.userid+"&id="+COURSEIDPASSEDIN;
                                                var win = window.open(url, 'easygradeuserreport');
                                                win.focus();
                                        }
                                }],itemId:"info5",
                        },
        ];
 	///////////////////////////////////////////////////
        //// define static part of model for the grid//////
        ///////////////////////////////////////////////////
        var MODEL = [   { name:'first',         type:'string'}, 
                        { name: 'last',         type: 'string' },
                        { name: 'name',         type: 'string' },
                        { name: 'perm',         type: 'int' },
                        { name: 'courseid',     type: 'int' },
                        { name: 'userid',       type: 'int' },
                        { name: 'email',        type: 'string' },
                        { name: 'group',        type: 'string' },
        ];
	var MODEL_FIELDS = ['first','last','name','perm','courseid','userid','email','group'];
        //////////////////////////////////////////////////////
        // iterating through grade items and//////////////////
        //pushing on dynamic parts of column and model////////
        //////////////////////////////////////////////////////
        
        for (var i = 0; i < grade_items.length; i++){
                
                grade_items[i]['id'] = String(grade_items[i]['id']);
		if(grade_items[i]['type']=='category' && hide_categories){
			var hidden_var = true;
		}else{
			var hidden_var = false;
		}
		var feedback_name = grade_items[i]['feedback'];
                //column array points a gradeitem id to its readable name
                var image ="";
                if(grade_items[i]['type']!='category'){
			image = "<img id='"+grade_items[i]['gid']+"' class='header-icon' style='vertical-align:middle;margin-bottom:4px;' src='resources/manual_item.svg'/>";
		}
                COLUMNS.push({header: image+grade_items[i]['name'], 
				dataIndex: grade_items[i]['gid'], width:100,
				hidden:hidden_var,
				summaryType:function(records,values){//custom summary function that calculates averages without null values
					var count = 0;
					var total = 0;
					var length = values.length;
					for(var i =0; i< length;i++){
						if(values[i]!=-99999){// the value -99999 indicates a null value for the grade, and the score shouldn't be considered
							total = total+values[i];
							count++;
						}	
					}
					return total/count;
				},
                                tooltip: grade_items[i]['name'], 
				locked:grade_items[i]['locked'],//locked set in grade_grade_items function
				itemId:(grade_items[i]['type']+i+grade_items[i]['cat_name']).replace(/["'()%\[\]{}\\=+\s&#@\^,\.!\$\*-]/g,''),
                                renderer:function(value, metaData, record, rowIdx, colIdx, store, view){ 
					var data = store.getAt(rowIdx);
					var feedback_name = view.grid.columns[colIdx]['dataIndex'].substr(1)+'feedback';
					var grade_name = ""+view.grid.columns[colIdx]['text'].replace(/<img[^>]*>/g,"")+"";
					var new_value = value;
					if(value == -99999){// the value -99999 indicates a null value for the grade
						new_value =  "-";
					}
    					metaData.tdAttr = 'data-qtip="<strong>' + data.get('first').replace(/\\'/g,"'")+" "+data.get('last').replace(/\\'/g,"'")+
						"<p>"+grade_name+", Score: "+new_value+
						"</strong><p>"+data.get(feedback_name) + '"';
					return new_value;
                                },
                                summaryRenderer: function(value, summaryData, dataIndex) {
					if(isNaN(value)){
						return "-";
					}
					return Math.round(value*100)/100;
                                }
				
                }); 
                //model array defines which grade item ids each student should have a score for (specifying it as an int for now)
		var typeformat="float";
		if(grade_items[i]['gradetype']==2){
			typeformat="string";
		}
                MODEL.push({name: grade_items[i]['gid'], type:typeformat});
                MODEL.push({name: grade_items[i]['feedback'], type:'string'});
		MODEL_FIELDS.push(grade_items[i]['gid']);
		MODEL_FIELDS.push(grade_items[i]['feedback']);
        }
        COLUMNS.push({header:"",width:1,itemId:'empty'});//add empty column to fix bug where avg gets overwritten by first student score
        FEWCOLUMNS = COLUMNS.slice(0,1).concat(COLUMNS.slice(4));
