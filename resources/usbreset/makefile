
source := usbreset.c

CFLAGS = -Wall -O2

# OBJ := $(source:.c=.o)
BUILDDIR = ../../tmp/usbreset_builddir/
OBJ := $(addprefix $(BUILDDIR), $(source:.c=.o))

TARGET = ../../tmp/usbreset

all: $(BUILDDIR) $(TARGET)

$(TARGET): $(OBJ)
	$(CC) $^ $(LDFLAGS) $(PROJ_LDFLAGS) -o $@

$(BUILDDIR)%.o: %.c
	$(CC) -I. $(CFLAGS) $(PROJ_CFLAGS) -c $< -o $@

clean:
	rm -f $(OBJ) $(TARGET)

$(BUILDDIR):
	mkdir -p $(BUILDDIR)
