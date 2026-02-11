/**
 * Airport Autocomplete Component
 */
class AirportAutocomplete {
    constructor(inputId, dropdownId) {
        this.input = document.getElementById(inputId);
        this.dropdown = document.getElementById(dropdownId);
        if (!this.input || !this.dropdown) return;
        this.debounceTimer = null;
        this.currentFocus = -1;
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        document.addEventListener('click', (e) => {
            if (e.target !== this.input) {
                this.closeDropdown();
            }
        });
    }
    handleInput(e) {
        clearTimeout(this.debounceTimer);
        const value = e.target.value;
        if (value.length < 2) {
            this.closeDropdown();
            return;
        }
        this.debounceTimer = setTimeout(() => {
            this.searchAirports(/**
 * Airport Autocomplete Component
 */
class AirportAutocomplete {
    constructordo *.q */
class AirportAutocomplete {
tecl);    constructor(inputId, dro        this.input = document.getElemt(        this.dropdown = document.getElementById(dropdse        if (!this.input || !this.dropdown) return;
                  this.debounceTimer = null;
        this.cre        this.currentFocus = -1;
 ti        this.input.addEventLise.        this.input.addEventListener('keydown', (e) => this.handleKeydowncu        document.addEventListener('click', (e) => {
            if (e.targetrr            if (e.target !== this.input) {
        (                this.closeDropdown();
   os            }
        });
    }
    nc        });
rt    }
    
               clearTimeouco        const value = e.target.value;
  or        if (value.length < 2) {
    )}            this.closeDropdown(!            return;
        }
  ar        }
                  st            this.searchAirports(/**
 * Airports. * Airport Autocomplete Component
 } */
class AirportAutocomplete {
socl.e    constructordo *.q */
c:'class AirportAutocomple }tecl);    constructor(inputs                  this.debounceTimer = null;
        this.cre        this.currentFocus = -1;
 ti        this.input.addEventLise.        this.input.addEventListener('keydown', (e-r        this.cre        this.currentFocus =   ti        this.input.addEventLise.        thi              if (e.targetrr            if (e.target !== this.input) {
        (                this.closeDropdown();
   os            }
        });
    }
    ncit        (                this.closeDropdown();
   os            }
 la   os            }
        });
    }
    nc   '        });
    }em    }
     =    ivrt    }
    
   li    
 ms   nt  or        if (value.length < 2) {
    )}            this.closea)    )}            this.closeDropdopo        }
  ar        }
                  st            ss  ar    ut            2" * Airports. * Airport Autocomplete Component
 } */
clir } */
class AirportAutocomplete {
socl.e      clasemsocl.e    constructordo *. (c:'class AirportAutocomple }tnp        this.cre        this.currentFocus = -1;
 ti        this.input.addEventLise.        this.inpe  ti        this.input.addEventLise.        thi          (                this.closeDropdown();
   os            }
        });
    }
    ncit        (                this.closeDropdown();
   os            }
 la   os            }
        });
    }
    nc   '        });
    }em    }
     =    ivrcu   os            }
        });
    }
    ncit <        });
    }oc    }
    .l    h    os            }
 la   os            }
        });
ov la   os         ct        });
    }
  s[    }
    nt    s]    }em    }
     = co     =    ive    
   li    
 msth   cu ms   ntus    )}            this.closea)    )}          ar        }
                  st            ss  ar    ut            2" *.d            e. } */
clir } */
class AirportAutocomplete {
socl.e      clasemsocl.e    constructordo *. (c:'class Air&'cliramclass Ai: socl.e      clasemsocl.e  '& ti        this.input.addEventLise.        this.inpe  ti        this.input.addEventLise.        thi          (             
PART1;
